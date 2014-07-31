<?php

namespace Arbaf\FinancialApiBundle\Controller\Admin;

use Arbaf\FinancialApiBundle\Controller\BaseCRUDApiController;
use Arbaf\FinancialApiBundle\Entity\Group;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GroupsController
 * @package Arbaf\FinancialApiBundle\Controller\Admin
 */
class GroupsController extends BaseCRUDApiController
{
    function getRepositoryName()
    {
        return "ArbafFinancialApiBundle:Group";
    }

    function getNewEntity()
    {
        return new Group("no_name");
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
