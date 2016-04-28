<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Telepay\FinancialApiBundle\Financial\Currency;

/**
 * Class MethodsController
 * @package Telepay\FinancialApiBundle\Controller\Management\Admin
 */
class MethodsController extends RestApiController
{
    /**
     * @Rest\View()
     */
    public function index() {

        $services = $this->get('net.telepay.method_provider')->findAll();

        $allowed_services = [];
        if ($this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            $allowed_services = $services;
        }else{
            $admin = $this->get('security.context')->getToken()->getUser();
            $admin_services = $admin->getMethodsList();

            foreach($services as $method){
                if(in_array($method->getCname().'-'.$method->getType(), $admin_services)){

                    $methodsEntity = $this->get('net.telepay.method_provider')->findByCname($method->getCname().'-'.$method->getType());

                    $resp = array(
                        'cname' =>  $methodsEntity->getCname(),
                        'type' =>  $methodsEntity->getType(),
                        'currency'  =>  $methodsEntity->getCurrency(),
                        'scale' =>  Currency::$SCALE[$methodsEntity->getCurrency()],
                        'base64image'   =>  $methodsEntity->getBase64Image()
                    );

                    $allowed_services[] = $resp;
                }

            }

        }

        if ($this->get('security.authorization_checker')->isGranted('ROLE_SUPER_COMMERCE')) {
            //todo: add pos service
        }

        //TODO: add exchange service

        return $this->restV2(
            200,
            "ok",
            "Methods got successfully",
            $allowed_services
        );
    }

    /**
     * @Rest\View()
     */
    public function indexSwift() {

        //search all input methods and output methods and combine like btc_halcash_es
        $services = $this->get('net.telepay.swift_provider')->findAll();

        if ($this->get('security.authorization_checker')->isGranted('ROLE_SUPER_COMMERCE')) {
            //todo: add pos service
        }

        $swift_methods = array();

        foreach($services as $service){
            $methods = explode('-',$service);

            $method_in = $this->get('net.telepay.in.'.$methods[0].'.v1');
            $method_out = $this->get('net.telepay.out.'.$methods[1].'.v1');

            $swift = array();
            $swift['name'] = $method_in->getName().' to '.$method_out->getName();
            $swift['cname'] = $method_in->getCname().'-'.$method_out->getCname();
            $swift['orig_coin'] = $method_in->getCurrency();
            $swift['dst_coin']  = $method_out->getCurrency();
            $swift_methods[] = $swift;

        }

        //TODO: add exchange service

        return $this->restV2(
            200,
            "ok",
            "Services got successfully",
            $swift_methods
        );
    }

}
