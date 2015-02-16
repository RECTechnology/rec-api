<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/13/15
 * Time: 6:07 PM
 */

namespace Telepay\FinancialApiBundle\Financial;

interface MoneyStorage {
    public function getAmount();
    public function getAvailable();
    public function getCurrency();
}