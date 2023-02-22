<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace App\Controller\Management\Company;

use Doctrine\Common\Annotations\AnnotationException;
use App\Entity\Group;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\UserGroup;

class AccountController extends BaseApiController{

    function getRepositoryName()
    {
        return "FinancialApiBundle:Group";
    }

    function getNewEntity()
    {
        return new Group();
    }


    /**
     * @Rest\View
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function setAdmin(Request $request, $id){

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository($this->getRepositoryName())->find($id);

        if(!$company) throw new HttpException(404, 'Company not found');

        $userGroup = $em->getRepository('FinancialApiBundle:UserGroup')->findOneBy(array(
            'user'  =>  $user->getId(),
            'group' =>  $company->getId()
        ));

        if(!$userGroup) throw new HttpException(404, 'Change not allowed');

        if(!$userGroup->hasRole('ROLE_ADMIN')) throw new HttpException('You don\'t have the necessary permissions');

        $company->setKycManager($user);
        $em->flush();

        return $this->rest(204, "ok", 'Manager changed successfully');

    }

}