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
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashInInterface;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashOutInterface;
use Telepay\FinancialApiBundle\Financial\Currency;


class PaynetReferenceMethod extends BaseMethod{

    private $driver;

    public function __construct($name, $cname, $type, $currency, $emial_required, $base64Image, $container, $driver){
        parent::__construct($name, $cname, $type, $currency, $emial_required, $base64Image, $container);
        $this->driver = $driver;
    }

    public function getPayInInfo($amount)
    {

        $id = microtime(true)*100;
        $id = round($id);
        $description = 'ChipChap Payment';

        $barcode = $this->driver->request($id, $amount, $description);

        if($barcode['error_code'] == 0){
            $response = array(
                'amount'    =>  $amount,
                'currency'  =>  $this->getCurrency(),
                'scale' =>  Currency::$SCALE[$this->getCurrency()],
                'expires_in' => $barcode['expiration_date'],
                'received' => 0.0,
                'barcode'   =>  $barcode['barcode'],
                'paynet_id' =>  $barcode['id'],
                'status'    =>  'created',
                'final'     =>  false
            );

        }else{
            $response = $barcode;

        }

        return $response;
    }

    public function getPayInStatus($paymentInfo)
    {
        if(isset($paymentInfo['paynet_id'])){
            $client_reference = $paymentInfo['paynet_id'];

            $result = $this->driver->status($client_reference);

            $paymentInfo['status'] = $result['status'];
            $paymentInfo['paynet_status'] = $result['status_description'];
            if($result['status'] == 'success') $paymentInfo['final'] = true;
        }else{
            $paymentInfo['status'] = 'error';
            $paymentInfo['paynet_status'] = 'Not payned id found';
            $paymentInfo['final'] = true;
        }


        return $paymentInfo;
    }

    public function cancel($paymentInfo){

        // TODO: Implement cancel() method.

    }
}