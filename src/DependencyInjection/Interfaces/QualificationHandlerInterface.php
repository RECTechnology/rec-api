<?php

namespace App\DependencyInjection\Interfaces;

use App\Document\Transaction;

interface QualificationHandlerInterface
{
    public function createQualificationBattery(Transaction $tx);


}