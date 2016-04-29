<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 4:42 AM
 */

namespace Telepay\FinancialApiBundle\Financial\Methods;

use MongoDBODMProxies\__CG__\Telepay\FinancialApiBundle\Document\Transaction;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseMethod;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashInInterface;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashOutInterface;
use Telepay\FinancialApiBundle\Financial\Currency;

class SafetyPayMethod extends BaseMethod {

    private $driver;

    public function __construct($name, $cname, $type, $currency, $email_required, $base64Image, $container, $driver){
        parent::__construct($name, $cname, $type, $currency, $email_required, $base64Image, $container);
        $this->driver = $driver;
    }

    public function getPayInInfo($amount)
    {

        $currency = $this->getCurrency();
        $paymentInfo = $this->driver->request($currency, $amount);

        $paymentInfo['mxn_amount'] = $amount;
        $paymentInfo['scale'] = Currency::$SCALE[$currency];
        $paymentInfo['status'] = Transaction::$STATUS_CREATED;
        $paymentInfo['final'] = false;

        return $paymentInfo;

    }

    public function send($paymentInfo)
    {
        $paymentInfo['status'] = 'sending';

        //TODO send email with the payment information


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

    public function getPayOutInfo($request)
    {

    }

    public function notification($request, $paymentInfo){

        static $paramNames = array(
            'ApiKey',
            'RequestDateTime',
            'MerchantSalesID',
            'ReferenceNo',
            'CreationDateTime',
            'Amount',
            'CUrrencyID',
            'PaymentReferenceNo',
            'Status',
            'Signature'
        );

        //Get the parameters sent by POST and put them in $params array
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[$paramName] = $request->query->get($paramName, 'null');
        }

        $response = $this->driver->notification($params);

        if($response['status'] == 1){
            $paymentInfo['status'] = Transaction::$STATUS_RECEIVED;
        }

        return $paymentInfo;
    }

}