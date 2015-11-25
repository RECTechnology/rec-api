<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core;


interface CashInInterface {

    public function getPayInInfo($amount);
    public function getPayInStatus($paymentInfo);
    public function getCurrency();
}