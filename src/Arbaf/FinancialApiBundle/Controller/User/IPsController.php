<?php

namespace Arbaf\FinancialApiBundle\Controller;
use Arbaf\FinancialApiBundle\Entity\IP;

/**
 * Class IPsController
 * @package Arbaf\FinancialApiBundle\Controller
 */
class IPsController extends BaseCRUDApiController {

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
    public function createAction(){
        return parent::createAction();
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
    public function updateAction($id){
        return parent::updateAction($id);
    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        return parent::deleteAction($id);
    }

}
