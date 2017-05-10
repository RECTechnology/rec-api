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
use Telepay\FinancialApiBundle\Financial\Currency;
use Symfony\Component\HttpFoundation\Request;


class TeleingresoUSAMethod extends BaseMethod{

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

        $teleingreso = $this->driver->createIssue($amount/100);

        $paymentInfo['status'] = Transaction::$STATUS_CREATED;
        $paymentInfo['amount'] = $teleingreso['amount']*100;
        $paymentInfo['reference'] = $teleingreso;
        $paymentInfo['expires_in'] = 7*24*60*60;
        $paymentInfo['currency'] = 'USD';
        $paymentInfo['scale'] = Currency::$SCALE['USD'];
        $paymentInfo['final'] = false;

        return $paymentInfo;
    }

    public function getPayOutInfo($request){
        throw new HttpException(405, 'Method not implemented');
    }

    public function getPayOutInfoData($data){
        throw new HttpException(405, 'Method not implemented');
    }

    public function getPayInStatus($paymentInfo)
    {
        if($paymentInfo['status'] == Transaction::$STATUS_RECEIVED){
            $paymentInfo['status'] = Transaction::$STATUS_SUCCESS;
        }

        return $paymentInfo;
    }

    public function getPayOutStatus($paymentInfo)
    {

        throw new HttpException(405, 'Method not implemented');
    }

    public function cancel($paymentInfo){
        throw new HttpException(405, 'Method not implemented');
    }

    public function notification($params, $paymentInfo){

        $response = $this->driver->notification($params);

        if($response['status'] == 1){
            $paymentInfo['status'] = Transaction::$STATUS_RECEIVED;
            $paymentInfo['response'] = $response['response'];
        }else{
            $paymentInfo['response'] = $response['response'];
        }

        return $paymentInfo;
    }

}