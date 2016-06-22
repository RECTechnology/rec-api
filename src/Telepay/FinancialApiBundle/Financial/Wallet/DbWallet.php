<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 21/06/16
 * Time: 20:00
 */

namespace Telepay\FinancialApiBundle\Financial\Wallet;


use Telepay\FinancialApiBundle\Financial\CashInInterface;
use Telepay\FinancialApiBundle\Financial\MiniumBalanceInterface;
use Telepay\FinancialApiBundle\Financial\WalletInterface;

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
}