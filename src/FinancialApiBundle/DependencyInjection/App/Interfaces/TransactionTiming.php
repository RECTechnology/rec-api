<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 10:42 PM
 */

namespace App\FinancialApiBundle\DependencyInjection\App\Interfaces;

interface TransactionTiming {
    public function getCreated();
    public function getUpdated();
}