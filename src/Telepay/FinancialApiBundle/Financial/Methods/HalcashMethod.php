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
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseMethod;


class HalcashMethod extends BaseMethod{

    private $driver;
    private $container;
    private $logger;

    public function __construct($name, $cname, $type, $currency, $email_required, $base64Image, $container, $driver){
        parent::__construct($name, $cname, $type, $currency, $email_required, $base64Image, $container);
        $this->driver = $driver;
        $this->container = $container;
        $this->logger = $this->container->get('transaction.logger');
    }

    public function send($paymentInfo)
    {
        $this->logger->info('HALCASH METHOD-> SEND');
        $phone = $paymentInfo['phone'];
        $prefix = str_replace("+", "", $paymentInfo['prefix']);
        $amount = $paymentInfo['amount']/100;
        $reference = $paymentInfo['concept'];

        if($reference != 'FairToEarth' && $reference != 'HolyTx') $reference = 'ChipChap';

        $this->logger->info('HALCASH METHOD-> reference=> '.$reference);
        $find_token = $paymentInfo['find_token'];
        if(isset($paymentInfo['pin'])){
            $pin = $paymentInfo['pin'];
        }else{
            $pin = rand(1000,9999);
            $paymentInfo['pin'] = $pin;
        }

        $this->logger->info('HALCASH METHOD-> currency=> '.$this->getCurrency());
        try{
            if($this->getCurrency() == 'EUR'){
                $hal = $this->driver->sendV3($phone, $prefix, $amount, $reference.' '.$find_token, $pin);
            }else{
                $hal = $this->driver->sendInternational($phone, $prefix, $amount, $reference.' '.$find_token, $pin, 'PL', 'POL');
            }
        }catch (HttpException $e){
            $this->logger->error('HALCASH METHOD-> ERROR=> '.$e->getMessage());
            $this->sendMail($e->getMessage(), $e->getStatusCode(), $paymentInfo);
            throw new Exception($e->getMessage(), $e->getStatusCode());
        }

        $this->logger->info('HALCASH METHOD-> errorcode=> '.$hal['errorcode']);

        if($hal['errorcode'] == 0){
            $paymentInfo['status'] = 'sent';
            $paymentInfo['final'] = false;
            $paymentInfo['halcashticket'] = $hal['halcashticket'];
        }elseif($hal['errorcode'] == 99){
            $paymentInfo['status'] = 'failed';
            $paymentInfo['final'] = false;
        }

        return $paymentInfo;
    }

    public function getPayInInfo($amount)
    {
        // TODO: Implement getPayInInfo() method.
    }

    public function getPayOutInfo($request)
    {
        $paramNames = array(
            'amount',
            'phone',
            'prefix',
            'concept'
        );

        $params = array();

        foreach($paramNames as $param){
            if(!$request->request->has($param)) throw new Exception( 'Parameter '.$param.' not found', 404);
            if($request->request->get($param) == null) throw new Exception( 'Parameter '.$param.' can\'t be null', 404);
            $params[$param] = $request->request->get($param);

        }

        $params['phone'] = preg_replace("/[^0-9,.]/", "", $params['phone']);
        $params['prefix'] = preg_replace("/[^0-9,.]/", "", $params['prefix']);
        if(!$this->checkPhone($params['phone'], $params['prefix'])) throw new Exception('Invalid phone.',400);

        if($request->request->has('pin')){
            $pin = $request->request->get('pin');
        }else{
            $pin = rand(1000,9999);
        }

        $find_token = substr(Random::generateToken(), 0, 6);

        $params['prefix'] = str_replace("+", "", $params['prefix']);
        $params['find_token'] = $find_token;
        $params['pin'] = $pin;
        $params['final'] = false;
        $params['status'] = false;

        return $params;
    }

    public function getPayOutInfoData($data){
        $paramNames = array(
            'amount',
            'phone',
            'prefix',
            'concept'
        );

        $params = array();

        foreach($paramNames as $param){
            if(!array_key_exists($param, $data)) throw new HttpException(404, 'Parameter '.$param.' not found');
            if($data[$param] == null) throw new Exception( 'Parameter '.$param.' can\'t be null', 404);
            $params[$param] = $data[$param];
        }

        $params['phone'] = preg_replace("/[^0-9,.]/", "", $params['phone']);
        $params['prefix'] = preg_replace("/[^0-9,.]/", "", $params['prefix']);
        if(!$this->checkPhone($params['phone'], $params['prefix'])) throw new Exception('Invalid phone.',400);

        if(array_key_exists('pin', $data)){
            $pin = $data['pin'];
        }else{
            $pin = rand(1000,9999);
        }

        $find_token = substr(Random::generateToken(), 0, 6);
        $params['prefix'] = str_replace("+", "", $params['prefix']);
        $params['find_token'] = $find_token;
        $params['pin'] = $pin;
        $params['final'] = false;
        $params['status'] = false;
        return $params;
    }

    public function getPayInStatus($paymentInfo)
    {
        // TODO: Implement getPayInStatus() method.
    }

    public function getPayOutStatus($paymentInfo)
    {
        $halcashticket = $paymentInfo['halcashticket'];

        $hal = $this->driver->status($halcashticket);

        if($hal['errorcode']==0){

            switch($hal['estadoticket']){
                case 'Autorizada':
                    $paymentInfo['status'] = 'sent';
                    $paymentInfo['final'] = false;
                    break;
                case 'Preautorizada':
                    $paymentInfo['status'] = 'sent';
                    $paymentInfo['final'] = false;
                    break;
                case 'Anulada':
                    $paymentInfo['status'] = Transaction::$STATUS_CANCELLED;
                    $paymentInfo['final'] = false;
                    break;
                case 'BloqueadaPorCaducidad':
                    $paymentInfo['status'] = Transaction::$STATUS_EXPIRED;
                    $paymentInfo['final'] = false;
                    break;
                case 'BloqueadaPorReintentos':
                    $paymentInfo['status'] = Transaction::$STATUS_LOCKED;
                    $paymentInfo['final'] = false;
                    break;
                case 'Devuelta':
                    $paymentInfo['status'] = Transaction::$STATUS_RETURNED;
                    $paymentInfo['final'] = false;
                    break;
                case 'Dispuesta':
                    $paymentInfo['status'] = 'withdrawn';
                    $paymentInfo['final'] = true;
                    break;
                case 'EstadoDesconocido':
                    break;
            }

        }

        return $paymentInfo;

    }

    public function cancel($paymentInfo){

        $halcashticket = $paymentInfo['halcashticket'];

        $response = $this->driver->cancelation($halcashticket, 'ChipChap cancelation');

        if($response['errorcode'] == 0){
            $paymentInfo['status'] = 'cancelled';
            $paymentInfo['halcashticket'] = false;
        }else{
            throw new Exception('(' . $response['errorcode'] . ')Transaction can\'t be cancelled',409);
        }

        return $paymentInfo;

    }

    public function checkPhone($phone, $prefix){
        if(strlen($prefix)<1){
            return false;
        }

        //SP xxxxxxxxx
        if($prefix == '34'){
            return strlen($phone)==9;
        }
        //PL xxxxxxxxx
        elseif($prefix == '48'){
            return strlen($phone)==9;
        }
        //GR xxxxxxxxx
        elseif($prefix == '30'){
            return strlen($phone)==10;
        }
        //GB 07xxx xxxxxx
        elseif($prefix == '44'){
            return strlen($phone)==11;
        }
        elseif(strlen($phone)>7){
            return true;
        }
        return false;
    }

    public function sendMail($error, $message, $paymentInfo){

        $no_reply = $this->container->getParameter('no_reply_email');

        $message = \Swift_Message::newInstance()
            ->setSubject('Halcash error ALERT')
            ->setFrom($no_reply)
            ->setTo(array(
                'cto@chip-chap.com',
                'pere@chip-chap.com'
            ))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:halcash_alert.html.twig',array(
                        'code_error'  =>  $error,
                        'message'    =>  $message,
                        'payment_info'  =>  $paymentInfo
                    ))
            )
            ->setContentType('text/html');

        $this->getContainer()->get('mailer')->send($message);
    }

}