<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 5/28/15
 * Time: 7:15 PM
 */

namespace Telepay\FinancialApiBundle\Financial\Wallet;


use Telepay\FinancialApiBundle\Financial\CashInInterface;
use Telepay\FinancialApiBundle\Financial\MoneyBundleInterface;
use Telepay\FinancialApiBundle\Financial\WalletInterface;

class KrakenWallet implements WalletInterface {

    private $krakenDriver;
    private $currency;
    private $krakenCurrencyNames = array(
        'BTC' => 'XXBT',
        'EUR' => 'ZEUR',
    );

    function __construct($krakenDriver, $currency)
    {
        $this->krakenDriver = $krakenDriver;
        $this->currency = $currency;
    }


    public function getAddress()
    {
        return $this->krakenDriver->QueryPrivate(
            'DepositAddresses',
            array(
                'asset' => 'XXBT',
                'method' => 'Bitcoin'
            )
        )['result'][0]['address'];
    }

    public function confirmReceived($amount, $token)
    {
        // TODO: Implement confirmReceived() method.
    }

    public function send(CashInInterface $dst, MoneyBundleInterface $money)
    {

        //TODO: donar d alta la address

        return $this->krakenDriver->QueryPrivate(
            'Withdraw',
            array(
                'asset' => $this->krakenCurrencyNames[$this->getCurrency()],
                'key' => 'concentradora',
                'amount' => $money->getAmount()
            )
        )['result'][0]['address'];
    }

    public function getBalance()
    {
        return $this->getAvailable();
    }

    public function getAvailable()
    {
        return $this->krakenDriver->QueryPrivate('Balance')['result'][$this->krakenCurrencyNames[$this->getCurrency()]];
    }

    public function getCurrency()
    {
        return $this->currency;
    }
}