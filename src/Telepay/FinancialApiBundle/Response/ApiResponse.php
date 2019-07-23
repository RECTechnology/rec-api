<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/6/14
 * Time: 8:59 PM
 */

namespace Telepay\FinancialApiBundle\Response;

class ApiResponse{
    private $code;
    private $message;
    private $data;
    public function __construct($code, $message, $data){
        $this->code=$code;
        $this->message=$message;
        $this->data=$data;
    }
}
