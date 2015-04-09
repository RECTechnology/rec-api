<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 4/7/15
 * Time: 1:52 AM
 */
namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Exchanges;

use GetFaircoin\Ticker;
use Telepay\FinancialApiBundle\Financial\Currency;

class FacEurExchange implements ExchangeInterface {

    private $getFaircoinTicker;

    function __construct(Ticker $getFaircoinTicker)
    {
        $this->getFaircoinTicker = $getFaircoinTicker;
    }


    public function getPrice()
    {
        $prices = $this->getFaircoinTicker->tick();
        return $prices[Currency::$EUR];
    }

    public function getFirst()
    {
        return Currency::$FAC;
    }

    public function getSecond()
    {
        return Currency::$EUR;
    }
}