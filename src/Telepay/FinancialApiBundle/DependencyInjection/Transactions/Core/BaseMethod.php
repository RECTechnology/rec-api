<?php

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Telepay\FinancialApiBundle\Document\Transaction;

abstract class BaseMethod extends AbstractMethod {

    private $container;

    public function __construct($name, $cname, $type, $currency, $emial_required, $base64Image, ContainerInterface $container){
        parent::__construct($name, $cname, $type, $currency, $emial_required, $base64Image);
        $this->container = $container;
    }

    /**
     * @return TransactionContextInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

}
