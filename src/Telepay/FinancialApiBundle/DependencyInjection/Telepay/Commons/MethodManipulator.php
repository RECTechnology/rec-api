<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons;

class MethodManipulator{
    public function underscoreToCamelcase($str){
        $func = create_function('$c', 'return strtoupper($c[1]);');
        return preg_replace_callback('/_([a-z])/', $func, $str);
    }
}