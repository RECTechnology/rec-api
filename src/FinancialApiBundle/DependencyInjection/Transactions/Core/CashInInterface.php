<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace App\FinancialApiBundle\DependencyInjection\Transactions\Core;


interface CashInInterface {

    public function getPayInInfo($account_id, $amount);
    public function getPayInStatus($paymentInfo);
    public function getCurrency();
    public function getMinimumAmount();
}