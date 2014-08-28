<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/27/14
 * Time: 3:13 PM
 */




namespace Telepay\FinancialApiBundle\DependencyInjection;


use Telepay\FinancialApiBundle\Entity\Service;

class ServicesRepository{

    private static $SERVICES = array(
        array(
            'id' => 0,
            'name' => 'Test',
            'role' => 'ROLE_SERVICES_TEST',
        ),
    );

    public function findById($id){
        foreach(ServicesRepository::$SERVICES as $serviceArray){
            if($serviceArray['id'] === $id){
                return new Service(
                    $serviceArray['id'],
                    $serviceArray['name'],
                    $serviceArray['role']
                );
            }
        }
        return null;
    }

    public function findByRole($role){
        foreach(ServicesRepository::$SERVICES as $serviceArray){
            if($serviceArray['role'] === $role){
                return new Service(
                    $serviceArray['id'],
                    $serviceArray['name'],
                    $serviceArray['role']
                );
            }
        }
        return null;
    }

    public function findByName($name){
        foreach(ServicesRepository::$SERVICES as $serviceArray){
            if($serviceArray['name'] === $name){
                return new Service(
                    $serviceArray['id'],
                    $serviceArray['name'],
                    $serviceArray['role']
                );
            }
        }
        return null;

    }

}