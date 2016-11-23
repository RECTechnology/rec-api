<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use Telepay\FinancialApiBundle\Entity\Group;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

class AccountController extends BaseApiController{

    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:Group";
    }

    function getNewEntity()
    {
        return new Group();
    }


    /**
     * @Rest\View
     */
    public function setAdmin(Request $request, $id){

        $user = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository($this->getRepositoryName())->find($id);

        if(!$company) throw new HttpException(404, 'Company not found');

        $userGroup = $em->getRepository('TelepayFinancialApiBundle:UserGroup')->findOneBy(array(
            'user'  =>  $user->getId(),
            'group' =>  $company->getId()
        ));

        if(!$userGroup) throw new HttpException(404, 'Change not allowed');

        if(!$userGroup->hasRole('ROLE_ADMIN')) throw new HttpException('You don\'t have the necessary permissions');

        $company->setKycManager($user);
        $em->persist($company);
        $em->flush();

        return $this->rest(204, 'Manager changed successfully');

    }

}