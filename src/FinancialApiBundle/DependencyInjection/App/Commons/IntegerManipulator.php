<?php

/**
 * Utils
 * Author: Lluis Santos
 */

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

class IntegerManipulator {
    public function isInteger($mixed){
        return preg_match( '/^\d*$/'  , $mixed);
    }
}