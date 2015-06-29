<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 5/28/15
 * Time: 7:10 PM
 */


namespace Telepay\FinancialApiBundle\Financial\Wallet;


use Telepay\FinancialApiBundle\Financial\CashInInterface;
use Telepay\FinancialApiBundle\Financial\Currency;
use Telepay\FinancialApiBundle\Financial\MiniumBalanceInterface;
use Telepay\FinancialApiBundle\Financial\MoneyBundleInterface;
use Telepay\FinancialApiBundle\Financial\WalletInterface;

class FullNodeWallet implements WalletInterface, MiniumBalanceInterface {

    private $nodeLink;
    private $currency;
    private $minBalance;

    function __construct($nodeLink, $currency, $minBalance = 0)
    {
        $this->nodeLink = $nodeLink;
        $this->currency = $currency;
        $this->minBalance = $minBalance;
    }

    public function send(CashInInterface $dst, $amount)
    {
        return $this->nodeLink->sendtoaddress($dst->getAddress(), $amount);
    }

    public function getBalance()
    {
        return $this->nodeLink->getbalance();
    }

    public function getCurrency()
    {
        return  $this->currency;
    }

    public function getAddress()
    {
        return $this->nodeLink->getnewaddress();
    }

    public function getMiniumBalance()
    {
        return $this->minBalance;
    }
}