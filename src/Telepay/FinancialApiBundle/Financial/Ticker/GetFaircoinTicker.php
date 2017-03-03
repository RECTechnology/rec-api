<?php

namespace Telepay\FinancialApiBundle\Financial\Ticker;

use GetFaircoin\Ticker as GetFaircoinDriver;
use Telepay\FinancialApiBundle\Financial\Currency;
use Telepay\FinancialApiBundle\Financial\TickerInterface;

class GetFaircoinTicker implements TickerInterface {

    private $getFaircoinTicker;
    private $outCurrency;

    function __construct(GetFaircoinDriver $getFaircoinDriver, $outCurrency)
    {
        $this->getFaircoinTicker = $getFaircoinDriver;
        $this->outCurrency= $outCurrency;
    }


    public function getPrice() {
        $prices = json_decode(file_get_contents("https://chain.fair-coin.org/download/ticker"));
        $ovars = get_object_vars($prices);
        return $ovars[$this->outCurrency]->last;
    }

    public function getInCurrency()
    {
        return Currency::$FAIRP;
    }

    public function getOutCurrency()
    {
        return $this->outCurrency;
    }
}