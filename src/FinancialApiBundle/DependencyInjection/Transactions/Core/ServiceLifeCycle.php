<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/23/15
 * Time: 12:28 AM
 */

namespace App\FinancialApiBundle\DependencyInjection\Transactions\Core;


use App\FinancialApiBundle\Document\Transaction;

interface ServiceLifeCycle {
    public function create(Transaction $transaction);
    public function update(Transaction $transaction, $data);
    public function check(Transaction $transaction);
    public function notificate(Transaction $transaction, $data);
}