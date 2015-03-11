<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 8:13 PM
 */


namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Exchanges;

use Telepay\FinancialApiBundle\Financial\Currency;

/**
 * Class Kraken
 * @package Telepay\FinancialApiBundle\DependencyInjection\Telepay\Exchanges
 */
class Kraken implements ExchangeInterface {

    /**
     * @var
     */
    private $krakenUrl;
    /**
     * @var
     */
    private $krakenUser;
    /**
     * @var
     */
    private $krakenPass;

    public function __construct($krakenUrl, $krakenUser, $krakenPass){

        $this->krakenUrl = $krakenUrl;
        $this->krakenUser = $krakenUser;
        $this->krakenPass = $krakenPass;
    }


    public function getCurrencyIn()
    {
        return Currency::$BTC;
    }

    public function getCurrencyOut()
    {
        return Currency::$EUR;
    }

    public function getB($currencyIn){

    }

    public function getA($currencyIn){

    }

}