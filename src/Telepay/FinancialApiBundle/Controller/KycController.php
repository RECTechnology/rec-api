<?php

namespace Telepay\FinancialApiBundle\Controller;

use Telepay\FinancialApiBundle\Controller\RestApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class KycController extends RestApiController{

    /**
     * new kyc register
     */
    public function newKyc(Request $request){
        return $this->restV2(200, "ok", "si", "si");
    }

    /**
     * update kyc register
     */
    public function updateKyc(Request $request, $id){
        return $this->restV2(200, "ok", "no", "no");
    }
}
