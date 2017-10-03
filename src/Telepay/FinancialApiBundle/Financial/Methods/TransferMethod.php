<?php

namespace Telepay\FinancialApiBundle\Financial\Methods;

use FOS\OAuthServerBundle\Util\Random;
use MongoDBODMProxies\__CG__\Telepay\FinancialApiBundle\Document\Transaction;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseMethod;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashInInterface;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashOutInterface;
use Telepay\FinancialApiBundle\Financial\Currency;

class TransferMethod extends BaseMethod {

    private $driver;
    private $container;

    public function __construct($name, $cname, $type, $currency, $email_required, $base64Image, $image, $container, $driver, $min_tier){
        parent::__construct($name, $cname, $type, $currency, $email_required, $base64Image, $image, $container, $min_tier);
        $this->driver = $driver;
        $this->container = $container;
    }

    public function getPayInInfo($amount)
    {
        $paymentInfo = $this->driver->request();

        $paymentInfo['amount'] = $amount;
        $paymentInfo['currency'] = $this->getCurrency();
        $paymentInfo['scale'] = Currency::$SCALE[$this->getCurrency()];
        $paymentInfo['status'] = Transaction::$STATUS_CREATED;
        $paymentInfo['final'] = false;

        return $paymentInfo;

    }

    public function send($paymentInfo)
    {
        if($paymentInfo['status'] == 'sending'){
            $paymentInfo['status'] = Transaction::$STATUS_SENT;
            $paymentInfo['final'] = true;

        }else{
            $paymentInfo['status'] = 'sending';
        }

        return $paymentInfo;

    }

    public function getPayInStatus($paymentInfo)
    {
        if($paymentInfo['status'] == Transaction::$STATUS_RECEIVED){
            $paymentInfo['status'] = Transaction::$STATUS_SUCCESS;
            $paymentInfo['final'] = true;
        }

        return $paymentInfo;

    }

    public function getPayOutStatus($id)
    {
        // TODO: Implement getPayOutStatus() method.
    }

    public function getPayOutInfo($request){
        $paramNames = array(
            'beneficiary',
            'iban',
            'amount',
            'bic_swift'
        );

        $params = array();

        foreach($paramNames as $param){
            if(!$request->request->has($param)) throw new HttpException(404, 'Parameter '.$param.' not found');
            $params[$param] = $request->request->get($param);

        }

        if($request->request->has('concept')){
            $concept = $request->request->get('concept');
        }else{
            $concept = 'Transfer transaction';
        }

        $params['concept'] = $concept;

        $params['find_token'] = $find_token = substr(Random::generateToken(), 0, 6);
        $params['currency'] = $this->getCurrency();
        $params['scale'] = Currency::$SCALE[$this->getCurrency()];
        $params['final'] = false;
        $params['status'] = false;

        return $params;
    }

    public function getPayOutInfoData($data){
        $paramNames = array(
            'beneficiary',
            'iban',
            'amount',
            'bic_swift'
        );

        $params = array();

        foreach($paramNames as $param){
            if(!array_key_exists($param, $data)) throw new HttpException(404, 'Parameter '.$param.' not found');
            $params[$param] = $data[$param];
        }

        if(array_key_exists('concept', $data)){
            $concept = $data['concept'];
        }else{
            $concept = 'Transfer transaction';
        }

        $params['concept'] = $concept;

        $params['find_token'] = $find_token = substr(Random::generateToken(), 0, 6);
        $params['currency'] = $this->getCurrency();
        $params['scale'] = Currency::$SCALE[$this->getCurrency()];
        $params['final'] = false;
        $params['status'] = false;

        return $params;
    }

    public function sendMail(Transaction $transaction){
        $no_reply = $this->container->getParameter('no_reply_email');
        $message = \Swift_Message::newInstance()
            ->setSubject('Transfer_out ALERT')
            ->setFrom($no_reply)
            ->setTo(array(
                'cto@chip-chap.com',
                'pere@chip-chap.com',
                'ceo@chip-chap.com'
            ))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:transfer_out_alert.html.twig',array(
                        'id'    =>  $transaction->getId(),
                        'type'  =>  $transaction->getType(),
                        'payment_infos'   =>  $transaction->getPayOutInfo(),
                        'transaction'   => $transaction
                    ))
            )
            ->setContentType('text/html');

        $this->getContainer()->get('mailer')->send($message);
    }

    public function getInfo(){
        return $this->driver->getInfo();
    }
}