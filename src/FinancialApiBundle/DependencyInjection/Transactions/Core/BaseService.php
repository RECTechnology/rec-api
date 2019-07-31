<?php

namespace App\FinancialApiBundle\DependencyInjection\Transactions\Core;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\FinancialApiBundle\Document\Transaction;

abstract class BaseService extends AbstractService {

    private $container;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, ContainerInterface $container){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image);
        $this->container = $container;
    }

    /**
     * @return TransactionContextInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    public function check(Transaction $t){
        return $t;
    }

}
