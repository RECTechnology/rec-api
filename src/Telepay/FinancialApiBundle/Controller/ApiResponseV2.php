<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/6/14
 * Time: 8:59 PM
 */

namespace Telepay\FinancialApiBundle\Controller;

class ApiResponseV2{
    private $status;
    private $message;
    private $data;
    public function __construct($status, $message, $data){
        $this->status=$status;
        $this->message=$message;
        $this->data=$data;
    }
}
