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
    private $type;

    public function __construct(KrakenDriver $krakenDriver, $outCurrency, $type){
        $this->krakenDriver = $krakenDriver;
        $this->outCurrency = $outCurrency;
        $this->krakenMarket = static::$krakenMarketsMap[$outCurrency];
        $this->type = $type;
    }

    public function getPrice(){
        if($this->type == 'bid') {
            $price = $this->krakenDriver->QueryPublic(
                'Ticker', array('pair' => $this->krakenMarket)
            )['result'][$this->krakenMarket]['b'][0];
            return $price;
        }
        elseif($this->type == 'ask'){
            $price = $this->krakenDriver->QueryPublic(
                'Ticker', array('pair' => $this->krakenMarket)
            )['result'][$this->krakenMarket]['b'][0];
            return 1.0/$price;
        }
    }

    public function getInCurrency(){
        if($this->type == 'bid') {
            return Currency::$BTC;
        }
        elseif($this->type == 'ask'){
            return $this->outCurrency;
        }
    }

    public function getOutCurrency(){
        if($this->type == 'bid') {
            return $this->outCurrency;
        }
        elseif($this->type == 'ask'){
            return Currency::$BTC;
        }
    }
}