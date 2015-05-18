<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 8:13 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Exchanges;

/**
 * Class InversedExchange
 * @package Telepay\FinancialApiBundle\DependencyInjection\Telepay\Exchanges
 */
class InversedExchange implements ExchangeInterface {

    private $exchange;

    public function __construct(ExchangeInterface $exchange){
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