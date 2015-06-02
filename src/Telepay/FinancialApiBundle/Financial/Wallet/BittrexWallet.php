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

    private $bittrexInteractor;

    function __construct(BittrexDriver $bittrexInteractor)
    {
        $this->bittrexInteractor = $bittrexInteractor;
    }


    public function getAddress()
    {
        // TODO: Implement getAddress() method.
    }

    public function send(CashInInterface $dst, MoneyBundleInterface $money)
    {
        // TODO: Implement send() method.
    }

    public function getAmount()
    {
        // TODO: Implement getAmount() method.
    }

    public function getAvailable()
    {
        // TODO: Implement getAvailable() method.
    }

    public function getCurrency()
    {
        // TODO: Implement getCurrency() method.
    }

    public function confirmReceived($amount, $token)
    {
        // TODO: Implement confirmReceived() method.
    }
}