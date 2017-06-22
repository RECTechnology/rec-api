<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 8:13 PM
 */

namespace Telepay\FinancialApiBundle\Financial\Ticker;

use Telepay\FinancialApiBundle\Financial\Currency;
use Telepay\FinancialApiBundle\Financial\Driver\BittrexDriver;
use Telepay\FinancialApiBundle\Financial\TickerInterface;

class BittrexTicker implements TickerInterface {
    private $bittrex;
    private $direction;
    private $currency;

    public function __construct(BittrexDriver $bittrex, $direction, $currency){
        $this->bittrex = $bittrex;
        $this->direction = $direction;
        $this->currency = $currency;
    }

    public function getPrice(){
        $resp = $this->bittrex->getOrderBook('BTC-' . $this->currency);
        if($resp->success != 1) throw new \LogicException("Failed getting FAC -> BTC price");
        $sum_btc = 0;
        $sum_other = 0;
        if($this->direction == 'fac_btc'){
            foreach($resp->result->buy as $bid){
                $sum_btc += $bid->Quantity * $bid->Rate;
                $sum_other += $bid->Quantity;
                // 3 bitcoins
                if($sum_btc>5){
                    return $sum_btc/$sum_other;
                }
            }
            return $sum_btc/$sum_other;
        }
        if($this->direction == 'btc_fac'){
            foreach($resp->result->sell as $ask){
                $sum_btc += $ask->Quantity * $ask->Rate;
                $sum_other += $ask->Quantity;
                // 3 bitcoins
                if($sum_btc>5){
                    return 1.0/($sum_btc/$sum_other);
                }
            }
            return 1.0/($sum_btc/$sum_other);
        }
    }

    public function getInCurrency(){
        if($this->direction == 'fac_btc')
            return Currency::$FAC;
        if($this->direction == 'btc_fac')
            return Currency::$BTC;
    }

    public function getOutCurrency(){
        if($this->direction == 'fac_btc')
            return Currency::$BTC;
        if($this->direction == 'btc_fac')
            return Currency::$FAC;
    }
}