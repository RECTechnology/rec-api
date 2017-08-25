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
        $sum_btc = 0.0;
        $sum_fiat = 0.0;

        if($this->type == 'bid') {
            $list = $this->krakenDriver->QueryPublic(
                'Depth', array('pair' => $this->krakenMarket)
            )['result'][$this->krakenMarket]['bids'];
        }
        elseif($this->type == 'ask') {
            $list = $this->krakenDriver->QueryPublic(
                'Depth', array('pair' => $this->krakenMarket)
            )['result'][$this->krakenMarket]['asks'];
        }

        foreach($list as $trans){
            $sum_fiat += $trans[1] * $trans[0];
            $sum_btc += $trans[1];
            // 30 bitcoins
            if($sum_btc>30){
                if($this->type == 'bid') {
                    return $sum_fiat/$sum_btc;
                }
                return 1.0/($sum_fiat/$sum_btc);
            }
        }
        if($this->type == 'bid') {
            return $sum_fiat/$sum_btc;
        }
        return 1.0/($sum_fiat/$sum_btc);
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