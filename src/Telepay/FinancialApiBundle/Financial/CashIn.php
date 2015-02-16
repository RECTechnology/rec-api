<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/13/15
 * Time: 6:35 PM
 */

namespace Telepay\FinancialApiBundle\Financial;

interface CashIn {
    public function receive($amount);
}