<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 8:13 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Exchanges;

/**
 * Class ChainedExchange
 * @package Telepay\FinancialApiBundle\DependencyInjection\Telepay\Exchanges
 */
class ChainedExchange implements ExchangeInterface {

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