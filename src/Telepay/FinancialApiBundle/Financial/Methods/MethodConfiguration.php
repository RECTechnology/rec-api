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

    public function __construct($fixed, $variable){
        $this->fixed = $fixed;
        $this->variable = $variable;

    }

    public function getFees(){

        $fee = new ServiceFee();
        $fee->setFixed($this->fixed);
        $fee->setVariable($this->variable);

        return $fee;
    }


}