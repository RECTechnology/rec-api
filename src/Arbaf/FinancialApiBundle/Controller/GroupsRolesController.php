<?php

namespace Arbaf\FinancialApiBundle\Controller;

use Arbaf\FinancialApiBundle\Entity\Group;
use Arbaf\FinancialApiBundle\Response\ApiResponseBuilder;
use Doctrine\DBAL\DBALException;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class GroupsController
 * @package Arbaf\FinancialApiBundle\Controller
 */
class GroupsRolesController extends FosRestController
{
    /**
     * @ApiDoc(
     *   section="Roles of Groups",
     *   description="Adds a role to a group by id",
     *   requirements={
     *      {
     *          "name"="id",
     *          "requirement"="[0-9]+",
     *          "dataType"="integer",
     *          "description"="Group id"
     *      },
     *      {
     *          "name"="rol_name",
     *          "requirement"="ROLE_[A-Z]+",
     *          "dataType"="string",
     *          "description"="Role name"
     *      },
     *   }
     * )
     *
     * @Rest\View
     */
    public function addRolesAction($id) {
        $em = $this->getDoctrine()->getManager();

        $request=$this->get('request_stack')->getCurrentRequest();
        $roleName = $request->get('role_name');
        if(empty($roleName))
            throw new HttpException(400, "Missing parameter 'role_name'");

        $groupRepository = $this->getDoctrine()->getRepository("ArbafFinancialApiBundle:Group");

        $group = $groupRepository->findOneBy(array('id'=>$id));

        if($group->hasRole($roleName)){
            throw new HttpException(409, "Duplicated resource");
        }
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
     * @ApiDoc(
     *   section="Roles of Groups",
     *   description="Removes a role from a group by id",
     *   requirements={
     *      {
     *          "name"="id",
     *          "requirement"="[0-9]+",
     *          "dataType"="integer",
     *          "description"="Group id"
     *      },
     *      {
     *          "name"="rol_name",
     *          "requirement"="ROLE_[A-Z]+",
     *          "dataType"="string",
     *          "description"="Role name"
     *      },
     *   }
     * )
     *
     * @Rest\View
     */
    public function deleteRolesAction($id, $rol_name) {
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();
        $groupRepository = $doctrine->getRepository("ArbafFinancialApiBundle:Group");

        $group = $groupRepository->findOneBy(array('id'=>$id));

        if(!$group->hasRole($rol_name))
            throw new HttpException(404, "Role not associated to specified group");

        $group->removeRole($rol_name);

        $em->persist($group);
        $em->flush();

        $resp = new ApiResponseBuilder(
            204,
            "Role deleted from group successfully",
            array()
        );

        $view = $this->view($resp, 204);

        return $this->handleView($view);
    }

}
