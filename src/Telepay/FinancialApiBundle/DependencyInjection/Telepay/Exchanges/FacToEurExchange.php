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

class FacToEuroExchange implements ExchangeInterface {

    private $getFaircoinTicker;

    function __construct(Ticker $getFaircoinTicker)
    {
        $this->getFaircoinTicker = $getFaircoinTicker;
    }


    public function getPrice()
    {
        $prices = $this->getFaircoinTicker()->tick(); //json_decode(file_get_contents("https://getfaircoin.net/api/ticker"));
        return $prices[Currency::$EUR];
    }

    public function getFirst()
    {
        return Currency::$FAC;
    }

    public function getSecond()
    {
        return $this->getFaircoinTicker;
    }
}