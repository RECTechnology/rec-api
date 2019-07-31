<?php

namespace App\FinancialApiBundle\Controller\Management\Integrator;

use App\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * Class ServicesController
 * @package App\FinancialApiBundle\Controller\Management\Integrator
 */
class ServicesController extends RestApiController {

    /**
     * @Rest\View()
     */
    public function read($service) {
        $services = $this->get('net.app.service_provider')->findByCname($service);

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
