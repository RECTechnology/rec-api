<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 5/28/15
 * Time: 7:15 PM
 */

namespace Telepay\FinancialApiBundle\Financial\Trader;


use Telepay\FinancialApiBundle\Financial\Driver\BittrexDriver;
use Telepay\FinancialApiBundle\Financial\TraderInterface;

class BittrexTrader implements TraderInterface {

    private $bittrexDriver;

    function __construct(BittrexDriver $bittrexDriver)
    {
        $this->bittrexDriver = $bittrexDriver;
    }

    public function buy($amount)
    {
        //$this->bittrexDriver->getOrderBook('BTC-FAIR');
    }

    public function sell($amount) {
        $buyOrders = $this->bittrexDriver->getOrderBook('BTC-FAIR', 'buy')->result;
        $sum = 0.0;
        foreach($buyOrders as $order){
            $sum += $order->Quantity;
            if($sum >= $amount){
                $resp = $this->bittrexDriver->sell('BTC-FAIR', $amount, $order->Rate);
                if(!$resp->success) throw new \LogicException("Sell action not worked");
                //echo "buy " . $amount . " by " . $order->Rate;
                return;
            }
        }
    }

    public function withdraw($amount, $address){
        return $this->bittrexDriver->withdraw('BTC', $amount, $address);
    }


}