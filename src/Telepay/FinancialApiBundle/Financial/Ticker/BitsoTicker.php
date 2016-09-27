<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 8:13 PM
 */

namespace Telepay\FinancialApiBundle\Financial\Ticker;

use Telepay\FinancialApiBundle\Financial\Currency;
use Telepay\FinancialApiBundle\Financial\Driver\BitsoDriver;
use Telepay\FinancialApiBundle\Financial\TickerInterface;

class BitsoTicker implements TickerInterface {

    private $bitsoDriver;
    private $direction;

    public function __construct(BitsoDriver $bitsoDriver, $direction){
        $this->bitsoDriver = $bitsoDriver;
        $this->direction = $direction;
    }

    public function getPrice(){
        $resp = $this->bitsoDriver->ticker('btc_mxn');
        if(!$resp->bid) throw new \LogicException("Failed getting BTC <-> MXN price");
        if($this->direction == 'btc_mxn')
            return $resp->bid;
        if($this->direction == 'mxn_btc')
            return 1.0/$resp->ask;
    }

    public function getInCurrency(){
        if($this->direction == 'btc_mxn')
            return Currency::$BTC;
        if($this->direction == 'mxn_btc')
            return Currency::$MXN;
    }

    public function getOutCurrency(){
        if($this->direction == 'btc_mxn')
            return Currency::$MXN;
        if($this->direction == 'mxn_btc')
            return Currency::$BTC;
    }
}