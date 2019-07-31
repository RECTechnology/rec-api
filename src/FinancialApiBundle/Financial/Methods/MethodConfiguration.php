<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 4:42 AM
 */

namespace App\FinancialApiBundle\Financial\Methods;

use App\FinancialApiBundle\Entity\ServiceFee;

class MethodConfiguration {

    private $fixed;
    private $variable;
    private $min_value;
    private $max_value;
    private $range;
    private $countries;
    private $currency;
    private $expires_in;
    private $delay;

    public function __construct($fixed, $variable, $min_value, $max_value, $range, $countries, $currency = null, $expires_in = null, $delay){
        $this->fixed = $fixed;
        $this->variable = $variable;
        $this->min_value = $min_value;
        $this->max_value = $max_value;
        $this->range = $range;
        $this->countries = $countries;
        $this->currency = $currency;
        $this->expires_in = $expires_in;
        $this->delay = $delay;

    }

    public function getInfo(){
        $response = array(
            'min_value' =>  $this->min_value,
            'max_value' =>  $this->max_value,
            'range'     =>  $this->range,
            'countries' =>  $this->countries,
            'currency'  =>  $this->currency,
            'expires_in'=>  $this->expires_in,
            'delay'     =>  $this->delay
        );

        return $response;
    }

    public function getFees(){

        $fee = new ServiceFee();
        $fee->setFixed($this->fixed);
        $fee->setVariable($this->variable);

        return $fee;
    }

}