<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 5/28/15
 * Time: 7:04 PM
 */


namespace Telepay\FinancialApiBundle\Financial;

interface MoneyBundleInterface {
    public function getAmount();
    public function getCurrency();
    public function getMessage();
}