<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Manager;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GroupsController
 * @package Telepay\FinancialApiBundle\Controller\Manager
 */
class CommissionsGroupController extends BaseApiController
{
    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:ServiceFee";
    }

    function getNewEntity()
    {
        return new ServiceFee();
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id){

        $user = $this->get('security.context')->getToken()->getUser();

        if(!$user->hasRole('ROLE_SUPER_ADMIN')){
            //check if this user is the group crator or superadmin, if not access not allowed
            $group = $user->getGroups()[0];
            $em = $this->getDoctrine()->getManager();
            $fee = $em->getRepository($this->getRepositoryName())->find($id);
            $feeGroup = $fee->getGroup();

            if($group->getId() != $feeGroup->getId()) throw new HttpException(409, 'You don\'t have the necessary permissions');
        }


        //negative values not allowed in variable and fixed field
        if($request->request->has('fixed')){
            if($request->request->get('fixed') < 0) throw new HttpException(404, 'Parameter fixed must be positive.');
        }

        if($request->request->has('variable')){
            if($request->request->get('variable') < 0) throw new HttpException(404, 'Parameter variable must be positive.');
        }

        return parent::updateAction($request, $id);
    }


}
