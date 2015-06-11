<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 8:13 PM
 */


namespace Telepay\FinancialApiBundle\Financial\Ticker;

use Payward\KrakenAPI as KrakenDriver;
use Telepay\FinancialApiBundle\Financial\Currency;
use Telepay\FinancialApiBundle\Financial\TickerInterface;

class KrakenTicker implements TickerInterface {

    private static $krakenMarketsMap = array(
        'EUR' => 'XXBTZEUR',
        'USD' => 'XXBTZUSD'
    );

    private $krakenDriver;
    private $outCurrency;
    private $krakenMarket;

    public function __construct(KrakenDriver $krakenDriver, $outCurrency){
        $this->krakenDriver = $krakenDriver;
        $this->outCurrency = $outCurrency;
        $this->krakenMarket = static::$krakenMarketsMap[$outCurrency];
    }

    public function getPrice()
    {
        $price = $this->krakenDriver->QueryPublic(
            'Ticker', array('pair' => $this->krakenMarket)
        )['result'][$this->krakenMarket]['b'][0];
        return $price;
    }

    public function getInCurrency()
    {
        return Currency::$BTC;
    }

    public function getOutCurrency()
    {
        return $this->outCurrency;
    }
}