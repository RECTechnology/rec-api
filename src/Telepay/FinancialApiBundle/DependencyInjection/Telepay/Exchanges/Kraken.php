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

    public function getPrice()
    {
        // TODO: Implement getPrice() method.
    }

    public function getFirst()
    {
        return Currency::$BTC;
    }

    public function getSecond()
    {
        return Currency::$EUR;
    }
}