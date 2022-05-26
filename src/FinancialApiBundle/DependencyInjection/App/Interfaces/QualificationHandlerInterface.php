<?php

namespace App\FinancialApiBundle\DependencyInjection\App\Interfaces;

use App\FinancialApiBundle\Document\Transaction;

interface QualificationHandlerInterface
{
    public function createQualificationBattery(Transaction $tx);


}