<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/13/15
 * Time: 6:35 PM
 */

namespace App\FinancialApiBundle\Financial;


interface CashOutInterface {
    public function send(CashInInterface $dst, $amount);
}