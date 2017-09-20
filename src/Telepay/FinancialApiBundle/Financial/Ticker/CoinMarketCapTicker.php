<?php

namespace Telepay\FinancialApiBundle\Financial\Ticker;

use Telepay\FinancialApiBundle\Financial\Currency;
use Telepay\FinancialApiBundle\Financial\TickerInterface;

class CoinMarketCapTicker implements TickerInterface {

    private $inCurrency;
    private $outCurrency;

    function __construct($inCurrency, $outCurrency){
        $this->inCurrency= $inCurrency;
        $this->outCurrency= $outCurrency;
    }

    public function getPrice() {
        $names_in = array(
            Currency::$CREA => 'creativecoin'
        );

        $names_out = array(
            Currency::$BTC => 'btc',
            Currency::$USD => 'usd'
        );

        $prices = json_decode(file_get_contents("https://api.coinmarketcap.com/v1/ticker/" . $names_in[$this->inCurrency]), true);
        return $prices[0]['price_' . $names_out[$this->outCurrency]] * 100000000;
    }

    public function getInCurrency(){
        return $this->inCurrency;
    }

    public function getOutCurrency(){
        return $this->outCurrency;
    }
}