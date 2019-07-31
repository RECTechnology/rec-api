<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 5/28/15
 * Time: 7:15 PM
 */

namespace App\FinancialApiBundle\Financial\Wallet;

use App\FinancialApiBundle\Financial\CashInInterface;
use App\FinancialApiBundle\Financial\Currency;
use App\FinancialApiBundle\Financial\Driver\BittrexDriver;
use App\FinancialApiBundle\Financial\MiniumBalanceInterface;
use App\FinancialApiBundle\Financial\MoneyBundleInterface;
use App\FinancialApiBundle\Financial\TraderInterface;
use App\FinancialApiBundle\Financial\WalletInterface;

class BittrexWallet implements WalletInterface, TraderInterface {

    private $bittrexDriver;
    private $currency;
    private $type = 'bittrex';
    private $waysOut;
    private $waysIn;

    function __construct(BittrexDriver $bittrexDriver, $currency, $waysOut, $waysIn){
        $this->bittrexDriver = $bittrexDriver;
        $this->currency = $currency;
        $this->waysOut = json_decode($waysOut);
        $this->waysIn = json_decode($waysIn);
    }


    public function getAddress()
    {
        $resp = $this->bittrexDriver->getDepositAddress(
            $this->getCurrency()
        );
        return $resp->result->Address;
    }

    public function send(CashInInterface $dst, $amount)
    {
        return $this->bittrexDriver->withdraw(
            $this->currency,
            $amount,
            $dst->getAddress()
        );
    }

    public function transfer(CashInInterface $dst, $amount){
        $resp = $this->bittrexDriver->withdraw(
            $this->currency,
            $amount,
            $dst->getAddress()
        );

        if(!$resp->success){
            return array(
                'sent' => false,
                'info' => 0
            );
        }
        return array(
            'sent' => true,
            //'info' => $resp->result->uuid
            'info' => json_encode($resp)
        );
    }

    public function getBalance()
    {
        $resp = $this->bittrexDriver->getBalance(
            $this->getCurrency()
        );
        return $resp->result->Balance;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getName()
    {
        return $this->type . '_' . $this->currency;
    }

    public function sell($amount) {
        $buyOrders = $this->bittrexDriver->getOrderBook('BTC-FAIR', 'buy')->result;
        $sum = 0.0;
        foreach($buyOrders as $order){
            $sum += $order->Quantity;
            if($sum >= $amount){
                $resp = $this->bittrexDriver->sell('BTC-FAIR', $amount, $order->Rate);
                if(!$resp->success) throw new \LogicException("Sell action not worked");
                return;
            }
        }
    }

    public function getPrice()
    {
        $resp = $this->bittrexDriver->ticker('BTC-FAIR');
        if($resp->success != 1) throw new \LogicException("Failed getting FAC -> BTC price");
        return $resp->result->Bid;
    }

    public function getInCurrency()
    {
        return $this->getCurrency();
    }

    public function getOutCurrency()
    {
        if($this->currency == Currency::$EUR) return Currency::$FAC;
        if($this->currency == Currency::$FAC) return Currency::$BTC;
    }

    public function getWaysOut()
    {
        return $this->waysOut;
    }

    public function getWaysIn()
    {
        return $this->waysIn;
    }
}