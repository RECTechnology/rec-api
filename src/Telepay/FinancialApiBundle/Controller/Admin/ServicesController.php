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
class ServicesController extends RestApiController
{
    /**
     * @Rest\View()
     */
    public function index() {
        $servicesRepo = $this->get('telepay.services');

        return $this->handleView($this->buildRestView(
            200,
            "Services got successfully",
            $servicesRepo->findAll()
        ));
    }

}
