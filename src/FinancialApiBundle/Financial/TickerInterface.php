<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 8:16 PM
 */


namespace App\FinancialApiBundle\Financial;

interface TickerInterface {
    public function getPrice();
    public function getInCurrency();
    public function getOutCurrency();
}