<?php


namespace App\FinancialApiBundle\Controller;

use FOS\RestBundle\Controller\ExceptionController;

class ApiExceptionController extends ExceptionController{

    protected function createExceptionWrapper(array $parameters) {
        //die(print_r($parameters, true));
        if (isset($parameters['errors'])) {
            return new RestExceptionWrapper($parameters['status_code'], $parameters['message'], $parameters['errors']);
        }
        return new RestExceptionWrapper("error", $parameters['message']);

    }

    protected function getExceptionMessage($exception){
        return $exception->getMessage();
    }
}

class RestExceptionWrapper {
    private $status;
    private $message;
    private $errors;

    public function __construct($status, $message, $errors = null){
        $this->status = $status;
        $this->message = $message;
        $this->errors = $errors;
    }
}