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

class FacCommonExchange implements ExchangeInterface {

    private $getFaircoinTicker;
    private $commonCurrency;

    function __construct(Ticker $getFaircoinTicker, $commonCurrency)
    {
        $this->getFaircoinTicker = $getFaircoinTicker;
        $this->commonCurrency= $commonCurrency;
    }


    public function getPrice() {
        $prices = $this->getFaircoinTicker->tick();
        $ovars = get_object_vars($prices);
        return $ovars[$this->commonCurrency]->last;
    }

    public function getInCurrency()
    {
        return Currency::$FAC;
    }

    public function getOutCurrency()
    {
        return $this->commonCurrency;
    }
}