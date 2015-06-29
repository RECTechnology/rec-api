<?php

namespace Telepay\FinancialApiBundle\Financial\Linker;

use Telepay\FinancialApiBundle\Financial\TraderInterface;
use Telepay\FinancialApiBundle\Financial\WayInterface;

class BasicTrader implements WayInterface {

    public $startNode, $endNode, $minAmount;

    public function __construct(TraderInterface $startNode, TraderInterface $endNode, $minAmount){
        $this->startNode = $startNode;
        $this->endNode = $endNode;
        $this->minAmount = $minAmount;
    }

    public function send($amount)
    {
        if($this->startNode->getOutCurrency() == $this->endNode->getInCurrency())
            throw new \LogicException("Trader nodes currencies cant be the same");

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
}