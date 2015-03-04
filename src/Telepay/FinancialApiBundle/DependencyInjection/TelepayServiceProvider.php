<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/27/14
 * Time: 3:13 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection;

use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\AbstractService;

class TelepayServiceProvider{

    private $servicesByCName;
    private $servicesByRole;

    public function __construct($services){
        $this->servicesByCName = array();
        $this->servicesByRole = array();
        foreach($services as $service){
            $this->servicesByCName[$service->getCName()] = $service;
            $this->servicesByRole[$service->getRole()] = $service;
        }
    }

    public function findAll(){
        $services = array();
        foreach($this->servicesByCName as $service){
            $services []= new AbstractService(
                $service->getName(),
                $service->getCname(),
                $service->getRole(),
                $service->getBase64Image()
            );
        }
        return $services;
    }

    public function findByCName($cname){
        return $this->servicesByCName[$cname];
    }

    public function findByRole($role){
        return $this->servicesByRole[$role];
    }

    public function findByRoles(array $roles){
        $services = array();
        foreach($roles as $role){
            if(array_key_exists($role, $this->servicesByRole))
                $services []= new AbstractService(
                    $this->servicesByRole[$role]->getName(),
                    $this->servicesByRole[$role]->getCname(),
                    $this->servicesByRole[$role]->getRole(),
                    $this->servicesByRole[$role]->getBase64Image()
                );
        }
        return $services;
    }



}
