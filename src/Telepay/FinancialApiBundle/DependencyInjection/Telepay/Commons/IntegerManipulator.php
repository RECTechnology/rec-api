<?php

/**
 * Utils
 * Author: Lluis Santos
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons;

class IntegerManipulator {
    public function isInteger($mixed){
        return preg_match( '/^\d*$/'  , $mixed);
    }
}