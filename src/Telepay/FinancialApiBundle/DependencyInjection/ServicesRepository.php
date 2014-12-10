<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/27/14
 * Time: 3:13 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection;


use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Entity\Service;

class ServicesRepository{

    private static $SERVICES = array(
        array(
            'id' => 1,
            'name' => 'Sample',
            'role' => 'ROLE_SERVICES_SAMPLE',
        ),
        array(
            'id' => 2,
            'name' => 'PaynetPayment',
            'role' => 'ROLE_SERVICES_PAYNET_PAYMENT',
        ),
        array(
            'id' => 3,
            'name' => 'PagoFacil',
            'role' => 'ROLE_SERVICES_PAGOFACIL',
        ),
        array(
            'id' => 4,
            'name' => 'PayU',
            'role' => 'ROLE_SERVICES_PAYU',
        ),
        array(
            'id' => 5,
            'name' => 'SafetyPay',
            'role' => 'ROLE_SERVICES_SAFETYPAY',
        ),
        array(
            'id' => 6,
            'name' => 'ToditoCash',
            'role' => 'ROLE_SERVICES_TODITOCASH',
        ),
        array(
            'id' => 7,
            'name' => 'Ukash',
            'role' => 'ROLE_SERVICES_UKASH',
        ),
        array(
            'id' => 8,
            'name' => 'PaynetReference',
            'role' => 'ROLE_SERVICES_PAYNET_REFERENCE',
        ),
        array(
            'id' => 9,
            'name' => 'Pademobile',
            'role' => 'ROLE_SERVICES_PADEMOBILE',
        ),
        array(
            'id' => 10,
            'name' => 'Ukashgenerate',
            'role' => 'ROLE_SERVICES_UKASHGENERATE',
        ),
        array(
            'id' => 11,
            'name' => 'Ukashredemption',
            'role' => 'ROLE_SERVICES_UKASHREDEMPTION',
        ),
        array(
            'id' => 12,
            'name' => 'HalcashSend',
            'role' => 'ROLE_SERVICES_HALCASHSEND',
        ),
        array(
            'id' => 13,
            'name' => 'HalcashPayment',
            'role' => 'ROLE_SERVICES_HALCASHPAYMENT',
        ),
        array(
            'id' => 14,
            'name' => 'Paysafecard',
            'role' => 'ROLE_SERVICES_PAYSAFECARD',
        ),
        array(
            'id' => 15,
            'name' => 'Multiva',
            'role' => 'ROLE_SERVICES_MULTIVA',
        ),
        array(
            'id' => 16,
            'name' => 'Sabadell',
            'role' => 'ROLE_SERVICES_SABADELL',
        )

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
                    $serviceArray['role']
                );
            }
        }
        throw new HttpException(404, "Service not found");

    }

}