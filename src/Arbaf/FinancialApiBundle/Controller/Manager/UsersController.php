<?php

namespace Arbaf\FinancialApiBundle\Controller\Manager;

use Arbaf\FinancialApiBundle\Controller\BaseCRUDApiController;
use Arbaf\FinancialApiBundle\Entity\User;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UsersController
 * @package Arbaf\FinancialApiBundle\Controller\Manager
 */
class UsersController extends BaseCRUDApiController
{
    function getRepositoryName()
    {
        return "ArbafFinancialApiBundle:User";
    }

    function getNewEntity()
    {
        return new User();
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
