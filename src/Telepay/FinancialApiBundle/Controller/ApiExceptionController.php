<?php


namespace Telepay\FinancialApiBundle\Controller;

use FOS\RestBundle\Controller\ExceptionController;

class ApiExceptionController extends ExceptionController{
    protected function getExceptionMessage($exception){
        return $exception->getMessage();
    }
}