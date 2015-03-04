<?php

namespace Telepay\FinancialApiBundle\Controller\Admin;

use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

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
        $services = $this->get('net.telepay.service_provider')->findAll();
        return $this->rest(
            200,
            "Services got successfully",
            $services
        );
    }

}
