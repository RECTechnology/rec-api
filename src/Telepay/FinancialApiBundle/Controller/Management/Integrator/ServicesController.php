<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Integrator;

use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\DependencyInjection\ServicesRepository;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * Class ServicesController
 * @package Telepay\FinancialApiBundle\Controller\Management\Integrator
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
