<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 5/28/15
 * Time: 7:10 PM
 */


namespace App\FinancialApiBundle\Financial\Wallet;


use App\FinancialApiBundle\Financial\CashInInterface;
use App\FinancialApiBundle\Financial\Currency;
use App\FinancialApiBundle\Financial\MiniumBalanceInterface;
use App\FinancialApiBundle\Financial\MoneyBundleInterface;
use App\FinancialApiBundle\Financial\WalletInterface;

class FullEthNodeWallet implements WalletInterface {

    private $nodeLink;
    private $currency;
    private $type = 'fullnode';
    private $waysOut;
    private $waysIn;

    function __construct($nodeLink, $currency, $waysOut, $waysIn){
        $this->nodeLink = $nodeLink;
        $this->currency = $currency;
        $this->waysOut = json_decode($waysOut);
        $this->waysIn = json_decode($waysIn);
    }

    public function send(CashInInterface $dst, $amount)
    {
        return $this->nodeLink->sendtoaddress($dst->getAddress(), $amount);
    }

    public function transfer(CashInInterface $dst, $amount){
        $resp =  $this->nodeLink->sendtoaddress($dst->getAddress(), $amount);
        if(!$resp){
            return array(
                'sent' => false,
                'info' => 0
            );
        }
        return array(
            'sent' => true,
            'info' => json_encode($resp)
        );
    }

    public function getBalance()
    {
        return $this->nodeLink->getbalance();
    }

    public function getFakeBalance()
    {
        return 1;
    }
    public function getCurrency()
    {
        return  $this->currency;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getName()
    {
        return $this->type . '_' . $this->currency;
    }

    public function getAddress()
    {
        return $this->nodeLink->getnewaddress();
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