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

    public function __construct($name, $cname, $type, $currency, $base64Image, $container, $driver){
        parent::__construct($name, $cname, $type, $currency, $base64Image, $container);
        $this->driver = $driver;
    }

    public function send($paymentInfo)
    {
        $phone = $paymentInfo['phone'];
        $prefix = str_replace("+", "", $paymentInfo['prefix']);
        $amount = $paymentInfo['amount']/100;
        $reference = $paymentInfo['concept'];

        $find_token = $paymentInfo['find_token'];
        if(isset($paymentInfo['pin'])){
            $pin = $paymentInfo['pin'];
        }else{
            $pin = rand(1000,9999);
            $paymentInfo['pin'] = $pin;
        }

        try{
            if($this->getCurrency() == 'EUR'){
                $hal = $this->driver->sendV3($phone,$prefix,$amount,'ChipChap '.$find_token,$pin);
            }else{
                $hal = $this->driver->sendInternational($phone,$prefix,$amount,'ChipChap '.$find_token,$pin, 'PL', 'POL');
            }
        }catch (HttpException $e){
            throw new Exception($e->getMessage(), $e->getStatusCode());
        }

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

    public function getPayInStatus($paymentInfo)
    {
        // TODO: Implement getPayInStatus() method.
    }

    public function getPayOutStatus($paymentInfo)
    {
        $halcashticket = $paymentInfo['halcashticket'];

        $hal = $this->driver->status($halcashticket);

        if($hal['errorcode']==0){

            switch($hal['estado']){
                case 'Autorizada':
                    $paymentInfo['status'] = Transaction::$STATUS_CREATED;
                    $paymentInfo['final'] = false;
                    break;
                case 'Preautorizada':
                    $paymentInfo['status'] = Transaction::$STATUS_CREATED;
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
            throw new Exception( 'Transaction can\'t be cancelled',409);
        }

        return $paymentInfo;

    }
}