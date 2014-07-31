<?php

namespace Arbaf\FinancialApiBundle\Controller;
use Arbaf\FinancialApiBundle\Entity\IP;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class IPsController
 * @package Arbaf\FinancialApiBundle\Controller
 */
class IPsController extends BaseApiController {

    function getRepositoryName() {
        return "ArbafFinancialApiBundle:IP";
    }

    function getNewEntity() {
        return new IP();
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
    public function createAction(Request $request){
        return parent::createAction($request);
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
