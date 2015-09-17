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
    public function read($service) {
        $services = $this->get('net.telepay.service_provider')->findByCname($service);

        $response = array(
            'cname' =>  $services->getCname(),
            'cash_direction' =>  $services->getCashDirection()
        );

        return $this->restV2(
            200,
            "ok",
            "Services got successfully",
            $response
        );
    }

}
