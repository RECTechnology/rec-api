<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 8:13 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Exchanges;

use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Providers\Bittrex;
use Telepay\FinancialApiBundle\Financial\Currency;

/**
 * Class FacBtcExchange
 * @package Telepay\FinancialApiBundle\DependencyInjection\Telepay\Exchanges
 */
class FacBtcExchange implements ExchangeInterface {

    private $bittrex;

    public function __construct(Bittrex $bittrex){
        $this->bittrex = $bittrex;
    }

    public function getPrice()
    {
        $resp = $this->bittrex->ticker('BTC-FAIR');
        if($resp->success != 1) throw new \LogicException("Failed getting FAC -> BTC price");
        return $resp->result->Bid;
    }

    public function getInCurrency()
    {
        return Currency::$FAC;
    }

    public function getOutCurrency()
    {
        return Currency::$BTC;
    }
}