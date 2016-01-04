<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 4:42 AM
 */

namespace Telepay\FinancialApiBundle\Financial\Methods;

use Telepay\FinancialApiBundle\Entity\ServiceFee;

class MethodConfiguration {

    private $fixed;
    private $variable;
    private $min_value;
    private $max_value;
    private $range;
    private $countries;

    public function __construct($fixed, $variable, $min_value, $max_value, $range, $countries){
        $this->fixed = $fixed;
        $this->variable = $variable;
        $this->min_value = $min_value;
        $this->max_value = $max_value;
        $this->range = $range;
        $this->countries = $countries;

    }

    public function getInfo(){
        $response = array(
            'min_value' =>  $this->min_value,
            'max_value' =>  $this->range,
            'range'     =>  $this->range,
            'countries' =>  $this->countries
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