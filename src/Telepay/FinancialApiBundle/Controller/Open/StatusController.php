<?php

namespace Telepay\FinancialApiBundle\Controller\Open;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\RestApiController;

class StatusController extends RestApiController {

    /**
     * @Rest\View
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function status(Request $request){

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            ["system_status" => "up"]
        );
    }
}