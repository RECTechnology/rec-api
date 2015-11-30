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
            'amount'    =>  $amount,
            'currency'  =>  $this->getCurrency(),
            'scale' =>  8,
            'address' => $address,
            'expires_in' => intval(1200),
            'received' => 0.0,
            'min_confirmations' => intval(1),
            'confirmations' => 0,
            'status'    =>  'created'
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

    public function getPayInStatus($paymentInfo)
    {
        $allReceived = $this->driver->listreceivedbyaddress(0, true);
//        $allReceived = $cryptoProvider->getreceivedbyaddress($address, 0);

        $amount = $paymentInfo['amount'];
        $address = $paymentInfo['address'];

        if($amount <= 100)
            $margin = 0;
        else
            $margin = 100;

        $allowed_amount = $amount - $margin;
        foreach($allReceived as $cryptoData){
            if($cryptoData['address'] === $address){
                $paymentInfo['received'] = doubleval($cryptoData['amount'])*1e8;
                if(doubleval($cryptoData['amount'])*1e8 >= $allowed_amount){
                    $paymentInfo['confirmations'] = $cryptoData['confirmations'];
                    if($paymentInfo['confirmations'] >= $paymentInfo['min_confirmations']){
                        $status = 'success';
                    }else{
                        $status = 'received';
                    }

                }else{
                    $status = 'created';
                }

                $paymentInfo['status'] = $status;
                return $paymentInfo;

            }

        }

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