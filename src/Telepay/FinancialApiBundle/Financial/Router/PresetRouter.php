<?php

namespace Telepay\FinancialApiBundle\Financial\Router;

use Telepay\FinancialApiBundle\Financial\MoneyStorageInterface;
use Telepay\FinancialApiBundle\Financial\RouterInterface;

class PresetRouter implements RouterInterface {

    public function getRoute(MoneyStorageInterface $startNode, MoneyStorageInterface $endNode)
    {
        // TODO: Implement getRoute() method.
    }
}