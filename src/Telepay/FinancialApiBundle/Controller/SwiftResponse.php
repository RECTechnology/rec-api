<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/6/14
 * Time: 8:59 PM
 */

namespace Telepay\FinancialApiBundle\Controller;

class SwiftResponse{
    private $status;
    private $message;
    private $id;
    private $amount;
    private $scale;
    private $currency;
    private $updated;
    private $pay_in_info;
    private $pay_out_info;


    public function __construct($status, $message,$id, $amount, $scale, $currency,$updated, $pay_in_info, $pay_out_info){
        $this->status=$status;
        $this->message=$message;
        $this->id=$id;
        $this->amount=$amount;
        $this->scale=$scale;
        $this->currency=$currency;
        $this->updated = $updated;
        $this->pay_in_info = $pay_in_info;
        $this->pay_out_info = $pay_out_info;
    }
}
