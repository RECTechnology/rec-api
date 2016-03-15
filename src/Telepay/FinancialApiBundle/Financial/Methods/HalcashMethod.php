<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\Financial\Methods;

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
        $prefix = $paymentInfo['prefix'];
        $amount = $paymentInfo['amount']/100;
        $reference = $paymentInfo['concept'];

        if(isset($paymentInfo['pin'])){
            $pin = $paymentInfo['pin'];
        }else{
            $pin = rand(1000,9999);
            $paymentInfo['pin'] = $pin;
        }

        try{
            if($this->getCurrency() == 'EUR'){
                $hal = $this->driver->sendV3($phone,$prefix,$amount,$reference,$pin);
            }else{
                $hal = $this->driver->sendInternational($phone,$prefix,$amount,$reference,$pin, 'PL', 'POL');
            }
        }catch (HttpException $e){
            throw new HttpException($e->getStatusCode(),$e->getMessage());
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
            if(!$request->request->has($param)) throw new HttpException(404, 'Parameter '.$param.' not found');
            $params[$param] = $request->request->get($param);

        }

        if($request->request->has('pin')){
            $pin = $request->request->get('pin');
        }else{
            $pin = rand(1000,9999);
        }

        $params['pin'] = $pin;
        $params['final'] = false;
        $params['status'] = false;

        return $params;
    }

    public function getPayInStatus($paymentInfo)
    {
        // TODO: Implement getPayInStatus() method.
    }

    public function getPayOutStatus($id)
    {
        // TODO: Implement getPayOutStatus() method.
    }

    public function cancel($paymentInfo){

        $halcashticket = $paymentInfo['halcashticket'];

        $response = $this->driver->cancelation($halcashticket, 'ChipChap cancelation');

        if($response['errorcode'] == 0){
            $paymentInfo['status'] = 'cancelled';
            $paymentInfo['halcashticket'] = false;
        }else{
            throw new HttpException(409, 'Transaction can\'t be cancelled');
        }

        return $paymentInfo;

    }
}