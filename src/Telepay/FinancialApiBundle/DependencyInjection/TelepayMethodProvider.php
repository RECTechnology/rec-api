<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/27/14
 * Time: 3:13 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection;

use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\AbstractMethod;

class TelepayMethodProvider{

    private $methodsByCName;

    public function __construct($methods){
        $this->servicesByCName = array();
        foreach($methods as $method){
            $this->methodsByCName[$method->getCName()] = $method;
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
                $method->getBase64Image()
            );
        }
        return $methods;
    }

    public function findByCName($cname){
        return $this->methodsByCName[$cname];
    }

    public function findByCNames(array $cnames){
        $methods = array();
        foreach($cnames as $cname){
            if(array_key_exists($cname, $this->methodsByCName))
                $methods []= new AbstractMethod(
                    $this->methodsByCName[$cname]->getName(),
                    $this->methodsByCName[$cname]->getCname(),
                    $this->methodsByCName[$cname]->getType(),
                    $this->methodsByCName[$cname]->getCurrency(),
                    $this->methodsByCName[$cname]->getBase64Image()
                );
        }
        return $methods;
    }

}
