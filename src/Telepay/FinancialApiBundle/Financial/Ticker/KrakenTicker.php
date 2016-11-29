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
            $bids = $this->krakenDriver->QueryPublic(
                'Depth', array('pair' => $this->krakenMarket)
            )['result'][$this->krakenMarket]['bids'];

            foreach($bids as $bid){
                $sum_fiat += $bid[0] * $bid[1];
                $sum_btc += $bid[0];
                // 100 bitcoins
                if($sum_btc>100){
                    return $sum_fiat/$sum_btc;
                }
            }
            return $sum_fiat/$sum_btc;
        }
        elseif($this->type == 'ask'){
            $asks = $this->krakenDriver->QueryPublic(
                'Depth', array('pair' => $this->krakenMarket)
            )['result'][$this->krakenMarket]['asks'];

            foreach($asks as $ask){
                $sum_fiat += $ask[0] * $ask[1];
                $sum_btc += $ask[0];
                // 100 bitcoins
                if($sum_btc>100){
                    return 1.0/($sum_fiat/$sum_btc);
                }
            }
            return 1.0/($sum_fiat/$sum_btc);
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