<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 5/28/15
 * Time: 7:10 PM
 */


namespace Telepay\FinancialApiBundle\Financial\Wallet;


use Telepay\FinancialApiBundle\Financial\CashInInterface;
use Telepay\FinancialApiBundle\Financial\MoneyBundleInterface;
use Telepay\FinancialApiBundle\Financial\WalletInterface;

class FullNode implements WalletInterface {

    private $nodeLink;

    function __construct($nodeLink)
    {
        $this->nodeLink = $nodeLink;
    }


    public function send(CashInInterface $dst, MoneyBundleInterface $money)
    {
        // TODO: Implement send() method.
    }

    public function getBalance()
    {
        // TODO: Implement getAmount() method.
    }

    public function getAvailable()
    {
        return $this->getBalance();
    }

    public function getCurrency()
    {
        // TODO: Implement getCurrency() method.
    }

    public function getAddress()
    {
        // TODO: Implement getAddress() method.
    }

    public function confirmReceived($amount, $token)
    {
        // TODO: Implement confirmReceived() method.
    }
}