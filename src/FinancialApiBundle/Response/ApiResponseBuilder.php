<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/30/14
 * Time: 9:20 PM
 */


namespace App\FinancialApiBundle\Response;


class ApiResponseBuilder {
    private $code;
    private $message;
    private $data;

    public function __construct($code, $message = "Data got successful", $data = array()){
        $this->code=$code;
        $this->message=$message;
        $this->data=$data;
    }

    public function __toString(){
        return json_encode(array(
            'code' => $this->code,
            'message' => $this->message,
            'data' => $this->data,
        ));
    }
}