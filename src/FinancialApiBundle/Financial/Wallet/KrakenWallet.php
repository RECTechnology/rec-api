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
use App\FinancialApiBundle\Financial\KrakenCashOutInterface;
use App\FinancialApiBundle\Financial\MiniumBalanceInterface;
use App\FinancialApiBundle\Financial\TraderInterface;
use App\FinancialApiBundle\Financial\WalletInterface;

class KrakenWallet implements WalletInterface, TraderInterface {

    private $krakenDriver;
    private $currency;
    private $type = 'kraken';
    private $waysOut;
    private $waysIn;
    private $krakenCurrencyNames = array(
        'BTC' => 'XXBT',
        'EUR' => 'ZEUR',
        'USD' => 'ZUSD'
    );

    private static $krakenMarketsMap = array(
        'EUR' => 'XXBTZEUR',
        'USD' => 'XXBTZUSD'
    );


    function __construct($krakenDriver, $currency, $waysOut, $waysIn){
        $this->krakenDriver = $krakenDriver;
        $this->currency = $currency;
        $this->waysOut = json_decode($waysOut);
        $this->waysIn = json_decode($waysIn);
    }


    public function getAddress(){
        $oneWeekLess6Hours = time() + (6.5 * 24 * 60 * 60);
        $old = $this->krakenDriver->QueryPrivate(
            'DepositAddresses',
            array(
                'asset' => 'XXBT',
                'method' => 'Bitcoin'
            )
        );
        $lastAddress = count($old['result'])-1;
        if($old['result'][$lastAddress]['expiretm'] > $oneWeekLess6Hours){
            return $old['result'][$lastAddress]['address'];
        }

        if(isset($old['result'][5])){
            return $old['result'][5]['address'];
        }

        return $this->krakenDriver->QueryPrivate(
            'DepositAddresses',
            array(
                'asset' => 'XXBT',
                'method' => 'Bitcoin',
                'new' => true
            )
        )['result'][0]['address'];
    }


    public function send(CashInInterface $dst, $amount)
    {
        if(!($dst instanceof KrakenCashOutInterface))
            throw new \LogicException("Cash out must be setup in the kraken exchange");

        return $this->krakenDriver->QueryPrivate(
            'Withdraw',
            array(
                'asset' => $this->krakenCurrencyNames[$this->getCurrency()],
                'key' => $dst->getKrakenLabel(),
                'amount' => $amount
            )
        )['result'];
    }


    public function getBalance()
    {
        $balances =$this->krakenDriver->QueryPrivate(
            'Balance'
        )['result'];

        if(isset($balances[$this->krakenCurrencyNames[$this->getCurrency()]])){
            return $balances[$this->krakenCurrencyNames[$this->getCurrency()]];
        }
        return 0;
    }

    public function getFakeBalance()
    {
        if($this->getCurrency() == 'BTC') return 2;
        if($this->getCurrency() == 'EUR') return 40000;
        if($this->getCurrency() == 'USD') return 0;
        return 0;
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

    public function sell($amount)
    {
        $buyOrders = $this->krakenDriver->QueryPublic(
            'Depth',
            array('pair' => 'XXBTZEUR')
        )['result']['XXBTZEUR']['bids'];

        $sum = 0.0;
        foreach($buyOrders as $order){
            $sum += $order[1];
            if($sum >= $amount){
                $resp = $this->krakenDriver->QueryPrivate(
                    'AddOrder',
                    array(
                        'pair' => 'XXBTZEUR',
                        'type' => 'sell',
                        'ordertype' => 'limit',
                        'price' => $order[0],
                        'volume' => $amount
                    )
                );
                if(count($resp['error']) > 0) throw new \LogicException("Sell action not worked");
                return $resp;
            }
        }
    }

    public function getPrice()
    {
        $price = $this->krakenDriver->QueryPublic(
            'Ticker', array('pair' => 'XXBTZEUR')
        )['result']['XXBTZEUR']['b'][0];
        return $price;
    }

    public function getInCurrency()
    {
        return Currency::$BTC;
    }

    public function getOutCurrency()
    {
        return Currency::$EUR;
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