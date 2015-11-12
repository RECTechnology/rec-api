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
use Telepay\FinancialApiBundle\Financial\Driver\HalcashTelepayDriver;
use Telepay\FinancialApiBundle\Financial\TickerInterface;

class HalcashTelepayTicker implements TickerInterface {

    private $halcashDriver;
    private $country;

    public function __construct(HalcashTelepayDriver $halcashDriver, $country){
        $this->halcashDriver = $halcashDriver;
        $this->country = $country;
    }

    public function getPrice()
    {
        $resp = $this->halcashDriver->ticker($this->country);

        return $resp;
    }

    public function getInCurrency()
    {
        if($this->country == 'ES'){
            return Currency::$EUR;
        }else{
            return Currency::$PLN;
        }


    }

    public function getOutCurrency()
    {

        if($this->country == 'ES'){
            return Currency::$PLN;
        }else{
            return Currency::$EUR;
        }

    }
}