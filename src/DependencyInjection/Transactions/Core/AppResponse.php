<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/1/15
 * Time: 8:56 PM
 */

namespace App\DependencyInjection\Transactions\Core;

use App\Document\Transaction;

interface AppResponse {
    public function init(Transaction $baseTransaction);
    public function getTransaction();
}