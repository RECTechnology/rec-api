<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 4:42 AM
 */

namespace Telepay\FinancialApiBundle\Financial\Methods;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashInInterface;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashOutInterface;
use Telepay\FinancialApiBundle\Financial\Currency;

class BtcMethod implements CashInInterface, CashOutInterface {

    private $driver;

    public function __construct($name, $cname, $type, $currency, $base64Image, $container, $driver){
        $this->driver = $driver;
    }

    public function getPayInInfo($amount)
    {
//        $address = $this->driver->getnewaddress();
        $address = 'dfghjklñ';

        if(!$address) throw new HttpException(404,'Service Temporally unavailable');

        $response = array(
            'btc_amount'    =>  $amount,
            'currency'  =>  $this->getCurrency(),
            'scale' =>  8,
            'address' => $address,
            'expires_in' => intval(1200),
            'received' => 0.0,
            'min_confirmations' => intval(1),
            'confirmations' => 0,
        );

        return $response;
    }



    public function getCurrency()
    {
        return Currency::$BTC;
    }

    public function send($paymentInfo)
    {
        // TODO: Implement send() method.
    }

    public function checkPayOutInfo($request)
    {
        // TODO: Implement checkPayOutInfo() method.
    }

    public function getPayInStatus($paymentInfo)
    {
        // TODO: Implement getPayInStatus() method.
    }

    public function getPayOutStatus($id)
    {
        // TODO: Implement getPayOutStatus() method.
    }

    public function getPayOutInfo($request)
    {
        // TODO: Implement getPayOutInfo() method.
    }
}