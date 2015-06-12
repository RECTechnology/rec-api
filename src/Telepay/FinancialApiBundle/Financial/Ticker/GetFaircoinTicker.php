<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 4/7/15
 * Time: 1:52 AM
 */
namespace Telepay\FinancialApiBundle\Financial\Ticker;

use GetFaircoin\Ticker as GetFaircoinDriver;
use Telepay\FinancialApiBundle\Financial\Currency;
use Telepay\FinancialApiBundle\Financial\TickerInterface;

class GetFaircoinTicker implements TickerInterface {

    private $getFaircoinTicker;
    private $outCurrency;

    function __construct(GetFaircoinDriver $getFaircoinDriver, $outCurrency)
    {
        $this->getFaircoinTicker = $getFaircoinDriver;
        $this->outCurrency= $outCurrency;
    }


    public function getPrice() {
        $prices = $this->getFaircoinTicker->tick();
        $ovars = get_object_vars($prices);
        return $ovars[$this->outCurrency]->last;
    }

    public function getInCurrency()
    {
        return Currency::$FAC;
    }

    public function getOutCurrency()
    {
        return $this->outCurrency;
    }
}