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

    public function __construct(BittrexDriver $bittrex, $direction){
        $this->bittrex = $bittrex;
        $this->direction = $direction;
    }

    public function getPrice(){
        $resp = $this->bittrex->getOrderBook('BTC-FAIR');
        if($resp->success != 1) throw new \LogicException("Failed getting FAC -> BTC price");
        $sum_btc = 0;
        $sum_fac = 0;
        if($this->direction == 'fac_btc'){
            foreach($resp->result->buy as $bid){
                $sum_btc += $bid->Quantity * $bid->Rate;
                $sum_fac += $bid->Quantity;
                // 3 bitcoins
                if($sum_btc>3){
                    return $sum_btc/$sum_fac;
                }
            }
            return $sum_btc/$sum_fac;
        }
        if($this->direction == 'btc_fac'){
            foreach($resp->result->sell as $ask){
                $sum_btc += $ask->Quantity * $ask->Rate;
                $sum_fac += $ask->Quantity;
                // 3 bitcoins
                if($sum_btc>3){
                    return 1.0/($sum_btc/$sum_fac);
                }
            }
            return 1.0/($sum_btc/$sum_fac);
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