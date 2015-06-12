<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 5/28/15
 * Time: 7:15 PM
 */

namespace Telepay\FinancialApiBundle\Financial\Wallet;

use Telepay\FinancialApiBundle\Financial\CashInInterface;
use Telepay\FinancialApiBundle\Financial\Driver\BittrexDriver;
use Telepay\FinancialApiBundle\Financial\MoneyBundleInterface;
use Telepay\FinancialApiBundle\Financial\WalletInterface;

class BittrexWallet implements WalletInterface {

    private $bittrexDriver;
    private $currency;

    function __construct(BittrexDriver $bittrexDriver, $currency)
    {
        $this->bittrexDriver = $bittrexDriver;
        $this->currency = $currency;
    }


    public function getAddress()
    {
        $resp = $this->bittrexDriver->getDepositAddress(
            $this->getCurrency()
        );
        return $resp->result->Address;
    }

    public function send(CashInInterface $dst, MoneyBundleInterface $money)
    {
        return $this->bittrexDriver->withdraw(
            $money->getCurrency(),
            $money->getAmount(),
            $dst->getAddress()
        );
    }

    public function getBalance()
    {
        $resp = $this->bittrexDriver->getBalance(
            $this->getCurrency()
        );
        return $resp->result->Balance;
    }

    public function getAvailable()
    {
        $resp = $this->bittrexDriver->getBalance(
            $this->getCurrency()
        );

        return $resp->result->Available;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function confirmReceived($amount, $token)
    {
        // TODO: Implement confirmReceived() method.
    }
}