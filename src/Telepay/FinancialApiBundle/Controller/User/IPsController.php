<?php

namespace Telepay\FinancialApiBundle\Controller;
use Telepay\FinancialApiBundle\Entity\IP;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class IPsController
 * @package Telepay\FinancialApiBundle\Controller
 */
class IPsController extends BaseApiController {

    function getRepositoryName() {
        return "TelepayFinancialApiBundle:IP";
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
