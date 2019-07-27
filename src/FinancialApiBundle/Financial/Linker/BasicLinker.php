<?php

namespace App\FinancialApiBundle\Financial\Linker;


use App\FinancialApiBundle\Financial\CashInInterface;
use App\FinancialApiBundle\Financial\CashOutInterface;
use App\FinancialApiBundle\Financial\WayInterface;

class BasicLinker implements WayInterface {

    public $startNode, $endNode, $minAmount, $timecCost, $moneyCost;

    public function __construct(CashOutInterface $startNode, CashInInterface $endNode, $minAmount, $timecCost, $moneyCost){
        $this->startNode = $startNode;
        $this->endNode = $endNode;
        $this->minAmount = $minAmount;
        $this->timeCost = $timecCost;
        $this->moneyCost = $moneyCost;
    }

    public function send($amount){
        if($this->startNode->getCurrency() != $this->endNode->getCurrency())
            throw new \LogicException("Linked nodes must have the same currency");
        //return 'transfer (' . json_encode($this->startNode->transfer($this->endNode, $amount)) . ')';
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

    public function getEstimatedDeliveryTime(){
        return time() + $this->timeCost;
    }
}