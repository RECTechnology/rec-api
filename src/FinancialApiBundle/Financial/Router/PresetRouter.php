<?php

namespace App\FinancialApiBundle\Financial\Router;

use App\FinancialApiBundle\Financial\MoneyStorageInterface;
use App\FinancialApiBundle\Financial\RouterInterface;

class PresetRouter implements RouterInterface {

    public function getRoute(MoneyStorageInterface $startNode, MoneyStorageInterface $endNode)
    {
        // TODO: Implement getRoute() method.
    }
}