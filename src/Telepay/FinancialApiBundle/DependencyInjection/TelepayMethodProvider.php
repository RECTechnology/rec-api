<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/27/14
 * Time: 3:13 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection;

use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\AbstractMethod;

class TelepayMethodProvider {

    private $methodsByCName;

    public function __construct(array $methods){
        $this->methodsByCName = array();
        foreach($methods as $method){
            $this->methodsByCName[$method->getCName().'-'.$method->getType()] = $method;
        }
    }

    public function findAll(){
        $methods = array();
        foreach($this->methodsByCName as $method){
            $methods []= new AbstractMethod(
                $method->getName(),
                $method->getCname(),
                $method->getType(),
                $method->getCurrency(),
                $method->getEmailRequired(),
                $method->getBase64Image(),
                $method->getImage(),
                $method->getMinTier()
            );

        }
        return $methods;
    }

    public function findByCName($cname){
        //this cname must be with this form method-type ex:btc-in
        return $this->methodsByCName[$cname];
    }

    public function findByCNames(array $cnames){
        //this cnames must be with this form method-type ex:btc-in
        $methods = array();
        foreach($cnames as $cname){
            if(array_key_exists($cname, $this->methodsByCName))
                $methods []= new AbstractMethod(
                    $this->methodsByCName[$cname]->getName(),
                    $this->methodsByCName[$cname]->getCname(),
                    $this->methodsByCName[$cname]->getType(),
                    $this->methodsByCName[$cname]->getCurrency(),
                    $this->methodsByCName[$cname]->getEmailRequired(),
                    $this->methodsByCName[$cname]->getBase64Image(),
                    $this->methodsByCName[$cname]->getImage(),
                    $this->methodsByCName[$cname]->getMinTier()
                );
        }
        return $methods;
    }

    public function isValidMethod($cname){
        //this cname must be with this form method-type ex:btc-cash_in
        if(isset($this->methodsByCName[$cname])){
            return true;
        }else{
            return false;
        }
    }

    public function findByTier($tier){
        $methods = array();
        if($tier === 'fairpay'){
            $arrayMethods = array('fac-in','fac-out');
            $arrayMethods = $this->findByCNames($arrayMethods);
        }else{
            $arrayMethods = $this->methodsByCName;
        }
        foreach($arrayMethods as $method){
            if($method->getMinTier() <= $tier)
                $methods []= new AbstractMethod(
                    $method->getName(),
                    $method->getCname(),
                    $method->getType(),
                    $method->getCurrency(),
                    $method->getEmailRequired(),
                    $method->getBase64Image(),
                    $method->getImage(),
                    $method->getMinTier()
                );
        }
        return $methods;
    }

}
