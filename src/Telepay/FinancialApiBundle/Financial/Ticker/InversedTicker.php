<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 8:13 PM
 */

namespace Telepay\FinancialApiBundle\Financial\Ticker;


use Telepay\FinancialApiBundle\Financial\TickerInterface;

class InversedTicker implements TickerInterface {

    private $exchange;

    public function __construct(TickerInterface $exchange){
        $this->exchange = $exchange;
    }

    public function getPrice()
    {
        return 1.0/$this->exchange->getPrice();
    }

    public function getInCurrency()
    {
        return $this->exchange->getOutCurrency();
    }

    public function getOutCurrency()
    {
        return $this->exchange->getInCurrency();
    }
}