<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/6/14
 * Time: 8:59 PM
 */

namespace Telepay\FinancialApiBundle\Controller;

class ApiResponseV3{
    private $status;
    private $message;
    private $id;
    private $amount;
    private $scale;
    private $currency;
    private $data;

    public function __construct($status, $message,$id, $amount, $scale, $currency, $data){
        $this->status=$status;
        $this->message=$message;
        $this->id=$id;
        $this->amount=$amount;
        $this->scale=$scale;
        $this->currency=$currency;
        $this->data=$data;
    }
}
