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
                $quantity = $order->Quantity - $sum + $amount;
                $resp = $this->bittrexDriver->sell('BTC-FAIR', $quantity, $order->Rate);
                if(!$resp->success) throw new \LogicException("Sell action not worked");
                break;
            }
            else {
                $resp = $this->bittrexDriver->sell('BTC-FAIR', $order->Quantity, $order->Rate);
                if(!$resp->success) throw new \LogicException("Sell action not worked");
            }
        }
    }

    public function withdraw(){
        //return $this->bittrexDriver->withdraw('BTC', 1.4, '13bkZYQgC46W4QHK3snE3NwN5RnDb1Jjsc');
    }

}