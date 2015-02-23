<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/27/14
 * Time: 3:13 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection;

class ServicesRepository2{

    private $servicesByCName;

    public function __construct($services){
        die(print_r($services, true));
        $this->servicesByCName = array();
        foreach($services as $service){
            $this->servicesByCName[$service->getCName()] = $service;
        }
    }

    public function findAll(){
        return array_values($this->servicesByCName);
    }

    public function findByCName($cname){
        return $this->servicesByCName[$cname];
    }

}