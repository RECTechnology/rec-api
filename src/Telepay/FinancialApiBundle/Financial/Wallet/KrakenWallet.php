<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 5/28/15
 * Time: 7:15 PM
 */

namespace Telepay\FinancialApiBundle\Financial\Wallet;


use Telepay\FinancialApiBundle\Financial\CashInInterface;
use Telepay\FinancialApiBundle\Financial\Currency;
use Telepay\FinancialApiBundle\Financial\KrakenCashOutInterface;
use Telepay\FinancialApiBundle\Financial\MiniumBalanceInterface;
use Telepay\FinancialApiBundle\Financial\TraderInterface;
use Telepay\FinancialApiBundle\Financial\WalletInterface;

class KrakenWallet implements WalletInterface, TraderInterface, MiniumBalanceInterface {

    private $krakenDriver;
    private $currency;
    private $krakenCurrencyNames = array(
        'BTC' => 'XXBT',
        'EUR' => 'ZEUR',
    );

    private $minBalance;


    private static $krakenMarketsMap = array(
        'EUR' => 'XXBTZEUR',
        'USD' => 'XXBTZUSD'
    );


    function __construct($krakenDriver, $currency, $minBalance = 0)
    {
        $this->krakenDriver = $krakenDriver;
        $this->currency = $currency;
        $this->minBalance = $minBalance;
    }


    public function getAddress(){
        $oneWeekLess6Hours = time() + (3 * 24 * 60 * 60);
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
        return $this->krakenDriver->QueryPrivate(
            'Balance'
        )['result'][$this->krakenCurrencyNames[$this->getCurrency()]];
    }

    public function getCurrency()
    {
        return $this->currency;
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

    public function getMiniumBalance()
    {
        return $this->minBalance;
    }
}