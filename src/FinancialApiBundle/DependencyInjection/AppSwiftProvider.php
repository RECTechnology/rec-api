<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/27/14
 * Time: 3:13 PM
 */

namespace App\FinancialApiBundle\DependencyInjection;

use App\FinancialApiBundle\DependencyInjection\Transactions\Core\AbstractMethod;

class AppSwiftProvider{

    private $methods;

    public function __construct($swift_methods){
        $this->methods = array();
        foreach($swift_methods as $method){
            $this->methods[] = $method;
        }
    }

    public function findAll(){

        return $this->methods;
    }

//    public function findByCName($cname){
//        //this cname must be with this form method-type ex:btc-in
//        return $this->methodsByCName[$cname];
//    }
//
//    public function findByCNames(array $cnames){
//        //this cnames must be with this form method-type ex:btc-in
//        $methods = array();
//        foreach($cnames as $cname){
//            if(array_key_exists($cname, $this->methodsByCName))
//                $methods []= new AbstractMethod(
//                    $this->methodsByCName[$cname]->getName(),
//                    $this->methodsByCName[$cname]->getCname(),
//                    $this->methodsByCName[$cname]->getType(),
//                    $this->methodsByCName[$cname]->getCurrency(),
//                    $this->methodsByCName[$cname]->getBase64Image()
//                );
//        }
//        return $methods;
//    }
//
    public function isValidMethod($cname){
        //this cname must be with this form method-type ex:btc-cash_in
        if(in_array($cname, $this->methods)){
            return true;
        }else{
            return false;
        }
    }

}
