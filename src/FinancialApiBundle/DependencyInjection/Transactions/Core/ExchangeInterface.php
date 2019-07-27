<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace App\FinancialApiBundle\DependencyInjection\Transactions\Core;


interface ExchangeInterface {
    public function getCurrencyIn();
    public function getCurrencyOut();
    public function getCname();
    public function getFields();
}