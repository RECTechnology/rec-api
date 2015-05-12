<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 4/7/15
 * Time: 1:52 AM
 */
namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Exchanges;

use GetFaircoin\Ticker;
use Symfony\Component\DependencyInjection\Container;
use Telepay\FinancialApiBundle\Financial\Currency;

class UsdEurExchange implements ExchangeInterface {

    function __construct()
    {
    }


    public function getPrice()
    {

        die(print_r('caca',true));

    }

    public function getFirst()
    {
        return Currency::$USD;
    }

    public function getSecond()
    {
        return Currency::$EUR;
    }
}