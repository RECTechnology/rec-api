<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 10:42 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Interfaces;

interface TransactionTiming {
    public function getCreated();
    public function getUpdated();
}