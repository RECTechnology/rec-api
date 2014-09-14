<?php

namespace Telepay\FinancialApiBundle\Controller\Admin;

use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\DependencyInjection\ServicesRepository;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\User;
use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use Doctrine\DBAL\DBALException;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ServicesController
 * @package Telepay\FinancialApiBundle\Controller\Admin
 */
class SystemController extends RestApiController
{
    /**
     * @Rest\View()
     */
    public function load() {
        return $this->handleView($this->buildRestView(
            200,
            "Load got successfully",
            sys_getloadavg()
        ));
    }

    /**
     * @Rest\View()
     */
    public function cores() {
        return $this->handleView($this->buildRestView(
            200,
            "Number of CPU Cores got successfully",
            system("nproc")
        ));
    }

}
