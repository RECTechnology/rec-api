<?php
namespace App\FinancialApiBundle\Exception;

/**
 * Class ExceptionWrapper
 * @package App\FinancialApiBundle\Exception
 */
class ExceptionWrapper {
    private $status;
    private $status_text;
    private $message;
    private $errors;

    public function __construct($status = 'error', $status_text = 'error', $message = 'Undefined error happened', $errors = null){
        $this->status = $status;
        $this->status_text = $status_text;
        $this->message = $message;
        if($errors != null) $this->errors = $errors;
    }
}