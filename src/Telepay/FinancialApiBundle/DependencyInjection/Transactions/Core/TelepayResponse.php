<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/1/15
 * Time: 8:56 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core;

use Telepay\FinancialApiBundle\Document\Transaction;

interface TelepayResponse {
    public function init(Transaction $baseTransaction);
    public function getTransaction();
}