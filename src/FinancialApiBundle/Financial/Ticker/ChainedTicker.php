<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 8:13 PM
 */

namespace App\FinancialApiBundle\Financial\Ticker;
use App\FinancialApiBundle\Financial\TickerInterface;

class ChainedTicker implements TickerInterface {

    private $exchangeChain;

    public function __construct(array $exchange_chain){
        $this->exchangeChain = $exchange_chain;
    }

    public function getPrice()
    {
        $finalPrice = 1.0;
        foreach($this->exchangeChain as $exchange){
            $finalPrice *= $exchange->getPrice();
        }
        return $finalPrice;
    }

    public function getInCurrency()
    {
        return $this->exchangeChain[0]->getInCurrency();
    }

    public function getOutCurrency()
    {
        $chainLength = count($this->exchangeChain);
        return $this->exchangeChain[$chainLength-1]->getOutCurrency();
    }
}