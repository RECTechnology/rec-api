<?php

/**
 * Utils
 * Author: Lluis Santos
 */

namespace App\DependencyInjection\Commons;

class IntegerManipulator {
    public function isInteger($mixed){
        return preg_match( '/^\d*$/'  , $mixed);
    }
}