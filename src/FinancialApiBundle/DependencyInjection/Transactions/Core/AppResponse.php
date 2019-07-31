<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/1/15
 * Time: 8:56 PM
 */

namespace App\FinancialApiBundle\DependencyInjection\Transactions\Core;

use App\FinancialApiBundle\Document\Transaction;

interface AppResponse {
    public function init(Transaction $baseTransaction);
    public function getTransaction();
}