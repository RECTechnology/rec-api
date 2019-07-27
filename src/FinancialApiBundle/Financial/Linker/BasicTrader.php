<?php

namespace App\FinancialApiBundle\Financial\Linker;

use App\FinancialApiBundle\Financial\TraderInterface;
use App\FinancialApiBundle\Financial\WayInterface;

class BasicTrader implements WayInterface {

    public $startNode, $endNode, $minAmount, $timecCost, $moneyCost;

    public function __construct(TraderInterface $startNode, TraderInterface $endNode, $minAmount, $timecCost, $moneyCost){
        $this->startNode = $startNode;
        $this->endNode = $endNode;
        $this->minAmount = $minAmount;
        $this->timeCost = $timecCost;
        $this->moneyCost = $moneyCost;
    }

    public function send($amount)
    {
        if($this->startNode->getOutCurrency() == $this->endNode->getInCurrency())
            throw new \LogicException("Trader nodes currencies cant be the same");
        //return 'transfer (' . json_encode($this->startNode->send($this->endNode, $amount)) . ')';
        return $this->startNode->transfer($this->endNode, $amount);
    }

    public function getStartNode()
    {
        return $this->startNode;
    }

    public function getEndNode()
    {
        return $this->endNode;
    }

    public function getMinAmount()
    {
        return $this->minAmount;
    }

    public function getTimeCost()
    {
        return $this->timeCost;
    }

    public function getMoneyCost()
    {
        return $this->moneyCost;
    }

    public function getEstimatedCost($amount)
    {
        return $amount * $this->moneyCost;
    }

    public function getEstimatedDeliveryTime()
    {
        return time() + $this->timeCost;
    }
}