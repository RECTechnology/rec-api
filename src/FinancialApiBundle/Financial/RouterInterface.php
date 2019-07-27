<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 5/29/15
 * Time: 1:57 AM
 */

namespace App\FinancialApiBundle\Financial;

interface RouterInterface {
    public function getRoute(MoneyStorageInterface $startNode, MoneyStorageInterface $endNode);
}