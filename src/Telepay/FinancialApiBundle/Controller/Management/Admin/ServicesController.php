<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * Class ServicesController
 * @package Telepay\FinancialApiBundle\Controller\Management\Admin
 */
class ServicesController extends RestApiController
{
    /**
     * @Rest\View()
     */
    public function index() {

        $services = $this->get('net.telepay.service_provider')->findAll();

        if ($this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            $allowed_services = $services;
        }else{
            $admin = $this->get('security.context')->getToken()->getUser();
            $admin_services = $admin->getServicesList();
            foreach($services as $service){
                if(in_array($service->getCname(),$admin_services)){
                    $allowed_services[] = $service;
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
            "Services got successfully",
            $allowed_services
        );
    }

    /**
     * @Rest\View()
     */
    public function indexSwift() {

        //search all input methods and output methods and combine like btc_halcash_es
        $services = $this->get('net.telepay.swift_provider')->findAll();

        if ($this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            $allowed_services = $services;
        }else{
            $admin = $this->get('security.context')->getToken()->getUser();
            $admin_services = $admin->getServicesList();
            foreach($services as $service){
                if(in_array($service->getCname(),$admin_services)){
                    $allowed_services[] = $service;
                }
            }

        }

        if ($this->get('security.authorization_checker')->isGranted('ROLE_SUPER_COMMERCE')) {
            //todo: add pos service
        }

        $methods_in = array();
        $methods_out = array();
        //TODO mix methods in with methods out
        foreach($allowed_services as $service){
            if($service->getType() == 'cash_in'){
                $methods_in[] = $service;
            }else{
                $methods_out[] = $service;
            }

        }

        $swift_methods = array();

        foreach($methods_in as $method_in){
            foreach($methods_out as $method_out){
                $swift = array();
                $swift['name'] = $method_in->getName().' to '.$method_out->getName();
                $swift['cname'] = $method_in->getCname().'-'.$method_out->getCname();
                $swift['orig_coin'] = $method_in->getCurrency();
                $swift['dst_coin']  = $method_out->getCurrency();
                $swift_methods[] = $swift;
            }
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
