<?php


namespace Telepay\FinancialApiBundle\Controller;


use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;

class ExceptionController extends RestApiController{

    public function show(Request $request, Exception $exception){
        return $this->handleRestView($exception->getCode(), $exception->getMessage());
    }
}