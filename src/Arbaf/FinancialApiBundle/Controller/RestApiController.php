<?php
/**
 * Created by PhpStorm.
 * User: Rick Moreno
 * Date: 7/30/14
 * Time: 8:38 PM
 */

namespace Arbaf\FinancialApiBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;

abstract class RestApiController extends FosRestController{

    protected function buildRestView($code, $message, $data){
        return $this->view(array(
            'message' => $message,
            'data' => $data,
        ), $code);
    }

}