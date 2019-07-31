<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 5/28/15
 * Time: 6:42 PM
 */

namespace App\FinancialApiBundle\Financial;

interface BankAccountInterface extends WalletInterface {
    public function getBank();
    public function getOwnerName();
    public function getAddress();
    public function getIBAN();
}