<?php

namespace Telepay\FinancialApiBundle\Financial\Ticker;

use Telepay\FinancialApiBundle\Financial\Currency;
use Telepay\FinancialApiBundle\Financial\TickerInterface;

class RecTicker implements TickerInterface {
    private $outCurrency;
    private $inCurrency;

    function __construct($inCurrency, $outCurrency){
        $this->inCurrency= $inCurrency;
        $this->outCurrency= $outCurrency;
    }

    public function getPrice() {
        return 1;
    }

    public function getInCurrency(){
        return $this->inCurrency;
    }

    public function getOutCurrency(){
        return $this->outCurrency;
    }
}