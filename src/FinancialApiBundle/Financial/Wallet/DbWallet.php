<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 21/06/16
 * Time: 20:00
 */

namespace App\FinancialApiBundle\Financial\Wallet;


use App\FinancialApiBundle\Financial\CashInInterface;
use App\FinancialApiBundle\Financial\MiniumBalanceInterface;
use App\FinancialApiBundle\Financial\WalletInterface;

class DbWallet implements WalletInterface, MiniumBalanceInterface {
    public function getAddress()
    {
        // TODO: Implement getAddress() method.
    }

    public function send(CashInInterface $dst, $amount)
    {
        // TODO: Implement send() method.
    }

    public function getMiniumBalance()
    {
        // TODO: Implement getMiniumBalance() method.
    }

    public function getBalance()
    {
        // TODO: Implement getBalance() method.
    }

    public function getCurrency()
    {
        // TODO: Implement getCurrency() method.
    }

    public function getType()
    {
        // TODO: Implement getType() method.
    }

    public function getName()
    {
        // TODO: Implement getName() method.
    }

    public function getWaysOut()
    {
        // TODO: Implement getWaysOut() method.
    }

    public function getWaysIn()
    {
        // TODO: Implement getWaysIn() method.
    }
}