<?php

namespace Telepay\FinancialApiBundle\Controller\Admin;

use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use Doctrine\DBAL\DBALException;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;

/**
 * Class GroupsController
 * @package Telepay\FinancialApiBundle\Controller
 */
class GroupsRolesController extends RestApiController {
    /**
     * @Param role_name
     * @Rest\View
     */
    public function addRolesAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();

        $roleName = $request->get('role_name');

        if(empty($roleName)) throw new HttpException(400, "Missing parameter 'role_name'");

        $groupRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:Group");

        $group = $groupRepository->findOneBy(array('id'=>$id));
        if(empty($group)) throw new HttpException(404, "Group not found");

        if($group->hasRole($roleName)) throw new HttpException(409, "Duplicated resource");

        $group->addRole($roleName);

        $em->persist($group);
        try{
            $em->flush();
        } catch(DBALException $e){
            if(preg_match('/SQLSTATE\[23000\]/',$e->getMessage()))
                throw new HttpException(409, "Duplicated resource");
            else
                throw new HttpException(500, "Unknown error occurred when save");
        }
        $resp = new ApiResponseBuilder(
            201,
            "Role added successfully",
            array('id' => $group->getId())
        );

        $view = $this->view($resp, 201);

        return $this->handleView($view);
    }

    /**
     * @Rest\View
     */
    public function deleteRolesAction($id, $rol_name) {
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();
        $groupRepository = $doctrine->getRepository("TelepayFinancialApiBundle:Group");

        $group = $groupRepository->findOneBy(array('id'=>$id));

        if(empty($group)) throw new HttpException(404, "Group not found");

        if(!$group->hasRole($rol_name))
            throw new HttpException(404, "Role not found in specified group");

        $group->removeRole($rol_name);

        $em->persist($group);
        $em->flush();

        $view = $this->buildRestView(
            204,
            "Role deleted from group successfully",
            array()
        );

        return $this->handleView($view);
    }
}
