<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 5/28/15
 * Time: 7:15 PM
 */

namespace Telepay\FinancialApiBundle\Financial\Trader;



use Telepay\FinancialApiBundle\Financial\MoneyStorageInterface;
use Telepay\FinancialApiBundle\Financial\TraderInterface;

class KrakenTrader implements TraderInterface {



    private $krakenDriver;

    public function __construct($krakenDriver){
        $this->krakenDriver = $krakenDriver;
    }


    public function sell($amount)
    {
        // TODO: Implement sell() method.
    }

    public function getPrice()
    {
        // TODO: Implement getPrice() method.
    }

    public function getInCurrency()
    {
        // TODO: Implement getInCurrency() method.
    }

    public function getOutCurrency()
    {
        // TODO: Implement getOutCurrency() method.
    }
}