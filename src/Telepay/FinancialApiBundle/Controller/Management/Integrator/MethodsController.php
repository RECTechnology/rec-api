<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Integrator;

use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Telepay\FinancialApiBundle\Financial\Currency;

/**
 * Class MethodsController
 * @package Telepay\FinancialApiBundle\Controller\Management\Integrator
 */
class MethodsController extends RestApiController {

    /**
     * @Rest\View()
     */
    public function read($method) {

        $methods = $this->get('net.telepay.method_provider')->findByCname($method);

        $response = array(
            'cname' =>  $methods->getCname(),
            'type' =>  $methods->getType(),
            'currency'  =>  $methods->getCurrency(),
            'scale' =>  Currency::$SCALE[$methods->getCurrency()],
            'base64image'   =>  $methods->getBase64Image()
        );

        return $this->restV2(
            200,
            "ok",
            "Methods got successfully",
            $response
        );
    }

    /**
     * @Rest\View()
     */
    public function index() {

        $methods = $this->get('net.telepay.method_provider')->findAll();

        return $this->restV2(
            200,
            "ok",
            "Methods got successfully",
            $methods
        );
    }

}
