<?php

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Telepay\FinancialApiBundle\Controller\Services\SampleResponse;
use Telepay\FinancialApiBundle\Document\Transaction;

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
