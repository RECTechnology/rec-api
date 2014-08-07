<?php
/**
 * Created by PhpStorm.
 * User: Rick Moreno
 * Date: 7/30/14
 * Time: 8:38 PM
 */

namespace Telepay\FinancialApiBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;

class RestApiController extends FosRestController{

    protected function buildRestView($code, $message, $data){
        return $this->view(new ApiResponse($code, $message, $data), $code);
    }

}