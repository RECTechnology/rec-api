<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\Financial\Methods;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashInInterface;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashOutInterface;


class PaynetReferenceMethod implements  CashInInterface{

    private $driver;
    private $currency;

    public function __construct($name, $cname, $type, $currency, $base64Image, $container, $driver){
        $this->driver = $driver;
        $this->currency = $currency;
    }

    public function getCurrency()
    {
        return $this->currency;
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
                'scale' =>  2,
                'expires_in' => $barcode['expiration_date'],
                'received' => 0.0,
                'barcode'   =>  $barcode['barcode'],
                'paynet_id' =>  $barcode['id'],
                'status'    =>  'created'
            );

        }else{
            $response = $barcode;

        }



        return $response;
    }

    public function getPayInStatus($paymentInfo)
    {
        // TODO: Implement getPayInStatus() method.
    }

    public function cancel($paymentInfo){

        // TODO: Implement cancel() method.

    }
}