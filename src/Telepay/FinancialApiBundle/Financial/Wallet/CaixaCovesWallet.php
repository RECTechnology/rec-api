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
use Telepay\FinancialApiBundle\Financial\WalletInterface;

class CaixaCovesWallet implements WalletInterface, KrakenCashOutInterface {

    private $ruralviaDriver;
    private $currency;
    private $waysOut;
    private $waysIn;

    function __construct($ruralviaDriver, $currency, $waysOut, $waysIn){
        $this->ruralviaDriver = $ruralviaDriver;
        $this->currency = $currency;
        $this->waysOut = json_decode($waysOut);
        $this->waysIn = json_decode($waysIn);
    }


    public function getAddress()
    {
        throw new \LogicException("Method getAddress() not implemented");
    }


    public function send(CashInInterface $dst, $amount)
    {
        throw new \LogicException("Method send() not implemented");
    }


    public function getBalance()
    {
        return 0;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getKrakenLabel()
    {
        return 'CAIXA COVES';
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