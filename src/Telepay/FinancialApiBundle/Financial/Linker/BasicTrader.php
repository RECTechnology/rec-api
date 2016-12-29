<?php

namespace Telepay\FinancialApiBundle\Financial\Linker;

use Telepay\FinancialApiBundle\Financial\TraderInterface;
use Telepay\FinancialApiBundle\Financial\WayInterface;

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
        return 'sent (' . $this->startNode->getName() . ' -> ' . $this->endNode->getName() . '):' . $amount . ' ' . $this->startNode->getCurrency();

        return $this->startNode->sell($amount);
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
}