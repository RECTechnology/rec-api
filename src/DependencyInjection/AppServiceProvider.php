<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/27/14
 * Time: 3:13 PM
 */

namespace App\DependencyInjection;

use App\DependencyInjection\Transactions\Core\AbstractService;

class AppServiceProvider{

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
                $service->getCashDirection(),
                $service->getCurrency(),
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
                    $this->servicesByRole[$role]->getCashDirection(),
                    $this->servicesByRole[$role]->getCurrency(),
                    $this->servicesByRole[$role]->getBase64Image()
                );
        }
        return $services;
    }

    public function findByCNames(array $cnames){
        $services = array();
        foreach($cnames as $cname){
            if(array_key_exists($cname, $this->servicesByCName))
                $services []= new AbstractService(
                    $this->servicesByCName[$cname]->getName(),
                    $this->servicesByCName[$cname]->getCname(),
                    $this->servicesByCName[$cname]->getRole(),
                    $this->servicesByCName[$cname]->getCashDirection(),
                    $this->servicesByCName[$cname]->getCurrency(),
                    $this->servicesByCName[$cname]->getBase64Image()
                );
        }
        return $services;
    }

}
