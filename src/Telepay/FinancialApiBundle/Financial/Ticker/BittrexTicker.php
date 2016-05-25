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

    public function __construct(BittrexDriver $bittrex){
        $this->bittrex = $bittrex;
    }

    public function getPriceOld()
    {
        $resp = $this->bittrex->ticker('BTC-FAIR');
        if($resp->success != 1) throw new \LogicException("Failed getting FAC -> BTC price");
        return $resp->result->Bid;
    }

    public function getPrice()
    {
        $resp = $this->bittrex->getOrderBook('BTC-FAIR');
        if($resp->success != 1) throw new \LogicException("Failed getting FAC -> BTC price");
        $sum_btc = 0;
        $sum_fac = 0;
        foreach($resp->result->sell as $ask){
            $sum_btc += $ask->Quantity * $ask->Rate;
            $sum_fac += $ask->Quantity;
            //1000 euros son 2.5 btc
            if($sum_btc>2.5){
                return $sum_btc/$sum_fac;
            }
        }
        return $sum_btc/$sum_fac;
    }

    public function getInCurrency()
    {
        return Currency::$FAC;
    }

    public function getOutCurrency()
    {
        return Currency::$BTC;
    }
}