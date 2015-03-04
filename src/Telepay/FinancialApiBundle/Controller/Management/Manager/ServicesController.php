<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Manager;

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
 * @package Telepay\FinancialApiBundle\Controller\Management\Manager
 */
class ServicesController extends RestApiController {

    /**
     * @Rest\View()
     */
    public function read($id) {
        $servicesRepo = new ServicesRepository();
        return $this->rest(
            200,
            "Service got successfully",
            $servicesRepo->findById($id)
        );
    }

}
