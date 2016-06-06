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


class TeleingresoMethod extends BaseMethod{

    private $driver;

    public function __construct($name, $cname, $type, $currency, $email_required, $base64Image, $container, $driver){
        parent::__construct($name, $cname, $type, $currency, $email_required, $base64Image, $container);
        $this->driver = $driver;
    }

    public function send($paymentInfo)
    {
        // TODO: Implement getPayInInfo() method.
    }

    public function getPayInInfo($amount)
    {

        try{
            $teleingreso = $this->driver->createIssue($amount/100);

        }catch (HttpException $e){
            throw new Exception($e->getMessage(), $e->getStatusCode());
        }

        if($teleingreso['TxtCode'] == 0){
            $paymentInfo['status'] = 'created';
            $paymentInfo['amount'] = $teleingreso['amount']*100;
            $paymentInfo['TeleingresoStatus'] = $teleingreso['transactionId'];
            $paymentInfo['TeleingresoId'] = $teleingreso['TxtDescription'];
            $paymentInfo['ChargeId'] = $teleingreso['chargeId'];
            $paymentInfo['track'] = $teleingreso['track'];
            $paymentInfo['expireDateTime'] = $teleingreso['expireDateTime'];
            $paymentInfo['productionDate'] = $teleingreso['productionDate'];
            $paymentInfo['currency'] = $teleingreso['currency'];
            $paymentInfo['final'] = false;
        }else{
            $paymentInfo['status'] = 'failed';
            $paymentInfo['final'] = false;
            $paymentInfo['errorCode'] = $teleingreso['errCode'];
            $paymentInfo['errorDescription'] = $teleingreso['errDescription'];
        }

        return $paymentInfo;
    }

    public function getPayOutInfo($request)
    {

    }

    public function getPayInStatus($paymentInfo)
    {
        // TODO: Implement getPayInStatus() method.
    }

    public function getPayOutStatus($paymentInfo)
    {


    }

    public function cancel($paymentInfo){

    }

}