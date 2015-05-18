<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 8:16 PM
 */


namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Exchanges;

interface ExchangeInterface {
    public function getPrice();
    public function getInCurrency();
    public function getOutCurrency();
}