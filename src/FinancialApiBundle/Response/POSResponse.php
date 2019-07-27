<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/6/14
 * Time: 8:59 PM
 */

namespace App\FinancialApiBundle\Response;

class POSResponse{
    private $status;
    private $message;
    private $id;
    private $amount;
    private $scale;
    private $currency;
    private $created;
    private $updated;
    private $type;
    private $last_check;
    private $last_price_at;
    private $pos_name;
    private $pay_in_info;


    public function __construct($status, $message, $id, $amount, $scale, $currency, $created, $updated, $type, $last_check, $last_price_at, $pos_name, $pay_in_info){
        $this->status = $status;
        $this->message = $message;
        $this->id = $id;
        $this->amount = $amount;
        $this->scale = $scale;
        $this->currency = $currency;
        $this->created = $created;
        $this->updated = $updated;
        $this->type = $type;
        $this->last_check = $last_check;
        $this->last_price_at = $last_price_at;
        $this->pos_name = $pos_name;
        $this->pay_in_info = $pay_in_info;
    }
}
