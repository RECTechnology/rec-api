<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/6/14
 * Time: 8:59 PM
 */

namespace App\Response;

class ApiResponse{
    private $message;
    private $data;
    public function __construct($message, $data){
        $this->message=$message;
        $this->data=$data;
    }
}
