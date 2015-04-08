<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 4/7/15
 * Time: 1:52 AM
 */
namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Exchanges;

use Symfony\Component\Intl\Exception\NotImplementedException;
use Telepay\FinancialApiBundle\Financial\Currency;

class GetFaircoin implements ExchangeInterface {

    private $outCurrency;

    function __construct($outCurrency = 'EUR')
    {
        $this->outCurrency = $outCurrency;
    }


    public function getPrice()
    {
        $prices = json_decode(file_get_contents("https://getfaircoin.net/api/ticker"));
        if(isset($prices[$this->outCurrency]))
            return $prices[$this->outCurrency];
        else throw new NotImplementedException(
            "Provider does not implement the exchange in='".Currency::$FAC."' out='".$this->outCurrency."''"
        );
    }

    public function getFirst()
    {
        return Currency::$FAC;
    }

    public function getSecond()
    {
        return $this->outCurrency;
    }
}