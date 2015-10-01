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

        return $this->restV2(
            200,
            "ok",
            "Services got successfully",
            $allowed_services
        );
    }

}
