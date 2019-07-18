<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/6/14
 * Time: 8:59 PM
 */

namespace Telepay\FinancialApiBundle\Response;

class SwiftResponse{
    private $status;
    private $message;
    private $id;
    private $method;
    private $amount;
    private $scale;
    private $currency;
    private $created;
    private $updated;
    private $pay_in_info;
    private $pay_out_info;


    public function __construct($status, $message, $id, $method, $amount, $scale, $currency, $created, $updated, $pay_in_info, $pay_out_info){
        $this->status = $status;
        $this->message = $message;
        $this->id = $id;
        $this->method = $method;
        $this->amount = $amount;
        $this->scale = $scale;
        $this->currency = $currency;
        $this->created = $created;
        $this->updated = $updated;
        $this->pay_in_info = $pay_in_info;
        $this->pay_out_info = $pay_out_info;
    }
}
