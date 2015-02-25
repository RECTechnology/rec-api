<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/27/14
 * Time: 3:13 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection;


use Symfony\Component\HttpKernel\Exception\HttpException;

class ServicesRepository{

/**
 *

    );

    public function findAll(){
        return ServicesRepository::$SERVICES;
    }

    public function findById($id){
        foreach(ServicesRepository::$SERVICES as $serviceArray){
            if($serviceArray['id'] == $id){
                return new Service(
                    $serviceArray['id'],
                    $serviceArray['name'],
                    $serviceArray['cname'],
                    $serviceArray['base64_image'],
                    $serviceArray['role']
                );
            }
        }
        throw new HttpException(404, "Service not found");
    }

    public function findByRole($role){
        foreach(ServicesRepository::$SERVICES as $serviceArray){
            if($serviceArray['role'] == $role){
                return new Service(
                    $serviceArray['id'],
                    $serviceArray['name'],
                    $serviceArray['cname'],
                    $serviceArray['base64_image'],
                    $serviceArray['role']
                );
            }
        }
        throw new HttpException(404, "Service not found");
    }

    public function findByName($name){
        foreach(ServicesRepository::$SERVICES as $serviceArray){
            if($serviceArray['name'] == $name){
                return new Service(
                    $serviceArray['id'],
                    $serviceArray['name'],
                    $serviceArray['cname'],
                    $serviceArray['base64_image'],
                    $serviceArray['role']
                );
            }
        }
        throw new HttpException(404, "Service not found");
    }

    public function findByCName($name){
        return $this
        foreach(ServicesRepository::$SERVICES as $serviceArray){
            if($serviceArray['cname'] == $name){
                return new Service(
                    $serviceArray['id'],
                    $serviceArray['name'],
                    $serviceArray['cname'],
                    $serviceArray['base64_image'],
                    $serviceArray['role']
                );
            }
        }
        throw new HttpException(404, "Service not found");

    }
*/
}