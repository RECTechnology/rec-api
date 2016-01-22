<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Acl\Exception\Exception;
use Symfony\Component\Validator\Constraints\DateTime;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\AbancaService;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\Document\Transaction;

class CryptocapitalService extends BaseService{

    private $cryptocapitalProvider;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $cryptocapitalProvider, $container){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $container);
        $this->cryptocapitalProvider = $cryptocapitalProvider;
    }

    public function getFields(){
        return array(
            'currency', 'amount', 'email', 'description'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $amount = $baseTransaction->getDataIn()['amount'];
        $description = $baseTransaction->getDataIn()['description'];

        $id = $baseTransaction->getId();

        $currency = strtoupper($baseTransaction->getDataIn()['currency']);
        $email = $baseTransaction->getDataIn()['email'];

        try{
            $cryptocapital = $this->cryptocapitalProvider->request($currency, $amount, $email, $description, $id);
        }catch (\RuntimeException $r){
            throw new HttpException(400,"Error in the request execution: " . $r->getMessage());
        }


        if($cryptocapital === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        if($cryptocapital == 'fake'){
                $params = array(
                    'id'    =>  $id,
                    'date'  =>  $baseTransaction->getCreated(),
                    'sendCurrency'  =>  'EUR',
                    'receiveCurrency'   =>  'EUR',
                    'sendAmount'    =>  $amount,
                    'receiveAmount' =>  $amount,
                    'narrative' =>  $email.','.$description.'-'.$id
                );

            $this->_sendFakeEmail($currency, $amount, $email, $description, $id);

        }else{
            $params = $cryptocapital['params'];
        }

        if(isset($params['id'])){
            $response = array(
                "id"    =>  $params['id'],
                "date"  =>  $params['date'],
                "sendCurrency"  =>  $params['sendCurrency'],
                "receiveCurrency" =>  $params['receiveCurrency'],
                "sendAmount"    =>  $params['sendAmount'],
                "receiveAmount" =>  $params['receiveAmount'],
                "narrative" =>  $params['narrative']
            );
            $baseTransaction->setStatus(Transaction::$STATUS_SUCCESS);
            $baseTransaction->setDebugData($cryptocapital);
            $baseTransaction->setData($response);
            $baseTransaction->setDataOut($response);

        }else{
            if($params['msg'] == 'Insufficient funds'){
                $baseTransaction->setStatus(Transaction::$STATUS_FAILED);
                $baseTransaction->setDebugData($cryptocapital);
                $response = array(
                    'message'   =>  'Service temporally unavailable'
                );
                $baseTransaction->setData($response);
                $baseTransaction->setDataOut($response);
            }else{
                $baseTransaction->setStatus(Transaction::$STATUS_ERROR);
                $baseTransaction->setData($params);
                $baseTransaction->setDataOut($params);
            }
        }

        return $baseTransaction;

    }

    //Regenera la tpv con los mismos datos
    public function update(Transaction $transaction, $data){

        return $transaction;
    }

    public function cancel(Transaction $transaction,$data){

        throw new HttpException(400,'Method not implemented');

    }

    public function check(Transaction $transaction){

        return $transaction;
    }

    public function notificate(Transaction $transaction , $request){

        return $transaction;

    }

    private function _sendFakeEmail($currency, $amount, $email, $description, $id){
        //send an email like entropay to fake the transaction
        $fake = array(
            'id'    =>  $id,
            'date'  =>  "2015-11-10",
            'sendCurrency'  =>  'EUR',
            'receiveCurrency'   =>  'EUR',
            'sendAmount'    =>  $amount,
            'receiveAmount' =>  $amount,
            'narrative' =>  $email.','.$description.'-'.$id
        );

        $body = implode(",", $fake);

        $message = \Swift_Message::newInstance()
            ->setSubject('CRYPTOCAPITAL transaction')
            ->setFrom('no-reply@chip-chap.com')
            ->setTo(array(
                $email
            ))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:support.html.twig',
                        array(
                            'message'        =>  $body
                        )
                    )
            );

        $this->getContainer()->get('mailer')->send($message);

    }
}