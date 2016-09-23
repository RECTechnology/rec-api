<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\Financial\Methods;

use FOS\OAuthServerBundle\Util\Random;
use MongoDBODMProxies\__CG__\Telepay\FinancialApiBundle\Document\Transaction;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\DateTime;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseMethod;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashInInterface;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashOutInterface;
use Telepay\FinancialApiBundle\Financial\Currency;


class CryptocapitalMethod extends BaseMethod {

    private $driver;

    public function __construct($name, $cname, $type, $currency, $email_required, $base64Image, $container, $driver){
        parent::__construct($name, $cname, $type, $currency, $email_required, $base64Image, $container);
        $this->driver = $driver;
    }

    public function send($paymentInfo)
    {
        $currency = $paymentInfo['currency'];
        $amount = $paymentInfo['amount'];
        $email = $paymentInfo['email'];
        $description = $paymentInfo['concept'];
        $find_token = $paymentInfo['find_token'];

        try{
            $cryptocapital = $this->driver->request($currency, $amount, $email, $description, $find_token);
        }catch (\RuntimeException $r){
            throw new Exception($r->getMessage(), 400);
        }

        if($cryptocapital === false)
            throw new Exception("Service temporarily unavailable, please try again in a few minutes", 503);

        if($cryptocapital == 'fake'){
            $params = array(
                'id'    =>  $find_token,
                'date'  =>  new DateTime(),
                'sendCurrency'  =>  'EUR',
                'receiveCurrency'   =>  'EUR',
                'sendAmount'    =>  $amount,
                'receiveAmount' =>  $amount,
                'narrative' =>  $email.','.$description.'-'.$find_token
            );

            $this->_sendFakeEmail($currency, $amount, $email, $description, $find_token);

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

                throw new Exception('Insuficient founds', 403);

            }else{

                throw new Exception('Service temporally unavailable', 403);

            }
        }

        $paymentInfo['response'] = $response;
        $paymentInfo['final'] = true;
        $paymentInfo['status'] = 'sent';

        return $paymentInfo;
    }

    public function getPayOutInfoData($data){
        $paramNames = array(
            'amount',
            'email'
        );

        $params = array();

        foreach($paramNames as $param){
            if(!array_key_exists($param, $data)) throw new HttpException(404, 'Parameter '.$param.' not found');
            $params[$param] = $data[$param];
        }

        if(array_key_exists('concept', $data)){
            $concept = $data['concept'];
        }else{
            $concept = 'Cryptocapital transaction';
        }

        if (!filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception( 'Invalid email', 400);
        }

        $params['concept'] = $concept;

        $params['find_token'] = $find_token = substr(Random::generateToken(), 0, 6);
        $params['currency'] = $this->getCurrency();
        $params['scale'] = Currency::$SCALE[$this->getCurrency()];
        $params['final'] = false;
        $params['status'] = false;

        return $params;
    }

    public function getPayOutInfo($request){
        $paramNames = array(
            'amount',
            'email'
        );

        $params = array();

        foreach($paramNames as $param){
            if(!$request->request->has($param)) throw new HttpException(404, 'Parameter '.$param.' not found');
            $params[$param] = $request->request->get($param);

        }

        if($request->request->has('concept')){
            $concept = $request->request->get('concept');
        }else{
            $concept = 'Cryptocapital transaction';
        }

        if (!filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception( 'Invalid email', 400);
        }

        $params['concept'] = $concept;

        $params['find_token'] = $find_token = substr(Random::generateToken(), 0, 6);
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