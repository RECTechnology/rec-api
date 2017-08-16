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
        if($resp->success != 1) throw new \LogicException("Failed getting " . $this->currency . " -> BTC price");
        $sum_btc = 0;
        $sum_other = 0;
        if (strpos($this->direction, '_btc') !== false) {
            foreach($resp->result->buy as $bid){
                $sum_btc += $bid->Quantity * $bid->Rate;
                $sum_other += $bid->Quantity;
                // 3 bitcoins
                if($sum_btc>3){
                    return $sum_btc/$sum_other;
                }
            }
            return $sum_btc/$sum_other;
        }
        if (strpos($this->direction, 'btc_') !== false) {
            foreach($resp->result->sell as $ask){
                $sum_btc += $ask->Quantity * $ask->Rate;
                $sum_other += $ask->Quantity;
                // 3 bitcoins
                if($sum_btc>3){
                    return 1.0/($sum_btc/$sum_other);
                }
            }
            return 1.0/($sum_btc/$sum_other);
        }
    }

    public function getInCurrency(){
        if (strpos($this->direction, '_btc') !== false) {
            if (strpos($this->direction, 'fac_') !== false) {
                return Currency::$FAC;
            }
            elseif (strpos($this->direction, 'crea_') !== false) {
                return Currency::$CREA;
            }
            elseif (strpos($this->direction, 'eth_') !== false) {
                return Currency::$ETH;
            }
        }
        elseif (strpos($this->direction, 'btc_') !== false) {
            return Currency::$BTC;
        }
    }

    public function getOutCurrency(){
        if (strpos($this->direction, '_btc') !== false) {
            return Currency::$BTC;
        }
        elseif (strpos($this->direction, 'btc_') !== false) {
            if (strpos($this->direction, '_fac') !== false) {
                return Currency::$FAC;
            }
            elseif (strpos($this->direction, '_crea') !== false) {
                return Currency::$CREA;
            }
            elseif (strpos($this->direction, '_eth') !== false) {
                return Currency::$ETH;
            }
        }
    }
}