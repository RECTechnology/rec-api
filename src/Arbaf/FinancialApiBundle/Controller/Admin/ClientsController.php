<?php

namespace Arbaf\FinancialApiBundle\Controller\Admin;

use Arbaf\FinancialApiBundle\Controller\BaseCRUDApiController;
use Arbaf\FinancialApiBundle\Entity\Client;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ClientController
 * @package Arbaf\FinancialApiBundle\Controller
 */
class ClientsController extends BaseCRUDApiController {

    function getRepositoryName() {
        return 'ArbafFinancialApiBundle:Client';
    }

    function getNewEntity() {
        return new Client();
    }

    /**
     * @Rest\View
     */
    public function indexAction(){
        return parent::indexAction();
    }

    /**
     * @Rest\View
     */
    public function showAction($id){
        return parent::showAction($id);
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id){
        return parent::updateAction($request, $id);
    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        return parent::deleteAction($id);
    }


}
