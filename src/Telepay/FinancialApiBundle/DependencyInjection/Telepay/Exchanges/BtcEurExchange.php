<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 8:13 PM
 */


namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Exchanges;

use Payward\KrakenAPI;
use Telepay\FinancialApiBundle\Financial\Currency;

/**
 * Class BtcEurExchange
 * @package Telepay\FinancialApiBundle\DependencyInjection\Telepay\Exchanges
 */
class BtcEurExchange implements ExchangeInterface {

    private $kraken;

    public function __construct(KrakenAPI $kraken){
        $this->kraken = $kraken;
    }

    public function getPrice()
    {
        return $this->kraken->QueryPublic('Ticker', array('pair' => 'XXBTZEUR'));
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