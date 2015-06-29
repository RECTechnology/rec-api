<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 5/28/15
 * Time: 7:15 PM
 */

namespace Telepay\FinancialApiBundle\Financial\Wallet;

use Telepay\FinancialApiBundle\Financial\CashInInterface;
use Telepay\FinancialApiBundle\Financial\Currency;
use Telepay\FinancialApiBundle\Financial\Driver\BittrexDriver;
use Telepay\FinancialApiBundle\Financial\MiniumBalanceInterface;
use Telepay\FinancialApiBundle\Financial\MoneyBundleInterface;
use Telepay\FinancialApiBundle\Financial\TraderInterface;
use Telepay\FinancialApiBundle\Financial\WalletInterface;

class BittrexWallet implements WalletInterface, TraderInterface, MiniumBalanceInterface {

    private $bittrexDriver;
    private $currency;
    private $minBalance;

    function __construct(BittrexDriver $bittrexDriver, $currency, $minBalance = 0)
    {
        $this->bittrexDriver = $bittrexDriver;
        $this->currency = $currency;
        $this->minBalance = $minBalance;
    }


    public function getAddress()
    {
        $resp = $this->bittrexDriver->getDepositAddress(
            $this->getCurrency()
        );
        return $resp->result->Address;
    }

    public function send(CashInInterface $dst, $amount)
    {
        return $this->bittrexDriver->withdraw(
            $this->currency,
            $amount,
            $dst->getAddress()
        );
    }

    public function getBalance()
    {
        $resp = $this->bittrexDriver->getBalance(
            $this->getCurrency()
        );
        return $resp->result->Balance;
    }

    public function getCurrency()
    {
        return $this->currency;
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

    public function getPrice()
    {
        $resp = $this->bittrexDriver->ticker('BTC-FAIR');
        if($resp->success != 1) throw new \LogicException("Failed getting FAC -> BTC price");
        return $resp->result->Bid;
    }

    public function getInCurrency()
    {
        return $this->getCurrency();
    }

    public function getOutCurrency()
    {
        if($this->currency == Currency::$EUR) return Currency::$FAC;
        if($this->currency == Currency::$FAC) return Currency::$BTC;
    }

    public function getMiniumBalance()
    {
        return $this->minBalance;
    }
}