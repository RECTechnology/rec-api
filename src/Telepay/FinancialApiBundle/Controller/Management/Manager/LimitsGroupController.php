<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Manager;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GroupsController
 * @package Telepay\FinancialApiBundle\Controller\Manager
 */
class LimitsGroupController extends BaseApiController
{
    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:LimitDefinition";
    }

    function getNewEntity()
    {
        return new LimitDefinition();
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id){

        //check if this user pertenece al group de la fee
        $user = $this->get('security.context')->getToken()->getUser();
        if(!$user->hasRole('ROLE_SUPER_ADMIN')){
            $group = $user->getGroups()[0];
            $em = $this->getDoctrine()->getManager();
            $limit = $em->getRepository($this->getRepositoryName())->find($id);
            $limitGroup = $limit->getGroup();

            if($group->getId() != $limitGroup->getId()) throw new HttpException(409, 'You don\'t have the necessary permissions');
        }


        return parent::updateAction($request, $id);
    }


}
