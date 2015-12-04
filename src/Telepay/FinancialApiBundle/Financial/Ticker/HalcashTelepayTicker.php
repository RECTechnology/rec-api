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

    public function __construct(HalcashTelepayDriver $halcashDriver){
        $this->halcashDriver = $halcashDriver;
    }

    public function getPrice()
    {
        $resp = $this->halcashDriver->ticker('PL');

        return $resp;
    }

    public function getInCurrency()
    {
        return Currency::$PLN;

    }

    public function getOutCurrency()
    {
        return Currency::$EUR;

    }
}