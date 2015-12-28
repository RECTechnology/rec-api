<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\Financial\Methods;

use MongoDBODMProxies\__CG__\Telepay\FinancialApiBundle\Document\Transaction;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\DateTime;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseMethod;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashInInterface;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashOutInterface;
use Telepay\FinancialApiBundle\Financial\Currency;


class CryptocapitalMethod extends BaseMethod {

    private $driver;

    public function __construct($name, $cname, $type, $currency, $base64Image, $container, $driver){
        parent::__construct($name, $cname, $type, $currency, $base64Image, $container);
        $this->driver = $driver;
    }

    public function send($paymentInfo)
    {
        $currency = $paymentInfo['currency'];
        $amount = $paymentInfo['amount'];
        $email = $paymentInfo['email'];
        $description = $paymentInfo['description'];
//        $id = $paymentInfo['id'];
        $id = '4356543';
        try{
            $cryptocapital = $this->driver->request($currency, $amount, $email, $description, $id);
        }catch (\RuntimeException $r){
            throw new HttpException(400,$r->getMessage());
        }

        if($cryptocapital === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        if($cryptocapital == 'fake'){
            $params = array(
                'id'    =>  $id,
                'date'  =>  new DateTime(),
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

            $paymentInfo['narrative'] = $params['narrative'];

        }else{
            if($params['msg'] == 'Insufficient funds'){

                throw new HttpException(403, 'Insuficient founds');

            }else{

                throw new HttpException(403, 'Service temporally unavailable');

            }
        }

        $paymentInfo['response'] = $response;
        $params['final'] = true;
        $params['status'] = Transaction::$STATUS_SUCCESS;

        return $paymentInfo;
    }

    public function getPayOutInfo($request)
    {
        $paramNames = array(
            'amount',
            'email'
        );

        $params = array();

        foreach($paramNames as $param){
            if(!$request->request->has($param)) throw new HttpException(404, 'Parameter '.$param.' not found');
            $params[$param] = $request->request->get($param);

        }

        $params['currency'] = $this->getCurrency();
        $params['scale'] = Currency::$SCALE[$this->getCurrency()];
        $params['final'] = false;
        $params['status'] = false;

        return $params;
    }

    public function getPayOutStatus($id)
    {
        // TODO: Implement getPayOutStatus() method.
    }

    public function cancel($paymentInfo){

        // TODO: Implement cancel() method.

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