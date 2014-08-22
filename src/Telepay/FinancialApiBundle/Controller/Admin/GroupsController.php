<?php

namespace Telepay\FinancialApiBundle\Controller\Admin;

use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Entity\Group;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GroupsController
 * @package Telepay\FinancialApiBundle\Controller\Admin
 */
class GroupsController extends BaseApiController
{
    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:Group";
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

    /**
     * @Rest\View
     */
    public function addRole(Request $request, $id){
        $roleName = $request->get('role_name');
        $groupsRepo = $this->getRepository();
        $group = $groupsRepo->findOneBy(array('id'=>$id));
        if(empty($group)) throw new HttpException(404, 'Group not found');
        if(empty($roleName)) throw new HttpException(400, "Missing parameter 'role_name'");
        if($group->hasRole($roleName)) throw new HttpException(409, "Group has already the role");

        $group->addRole($roleName);
        $em = $this->getDoctrine()->getManager();
        $em->persist($group);

        try{
            $em->flush();
        } catch(DBALException $e){
            if(preg_match('/SQLSTATE\[23000\]/',$e->getMessage()))
                throw new HttpException(409, "Duplicated resource");
            else
                throw new HttpException(500, "Unknown error occurred when save");
        }

        return $this->handleView($this->buildRestView(200, "Role added successfully", array()));

    }

    /**
     * @Rest\View
     */
    public function deleteRole($id, $role_name){
        $groupsRepo = $this->getRepository();
        $group = $groupsRepo->findOneBy(array('id'=>$id));

        if(empty($group)) throw new HttpException(404, "Group not found");
        if(!$group->hasRole($role_name)) throw new HttpException(404, "Role not found in specified group");

        $group->removeRole($role_name);

        $em = $this->getDoctrine()->getManager();

        $em->persist($group);
        $em->flush();

        return $this->handleView($this->buildRestView(
            204,
            "Role deleted from group successfully",
            array()
        ));
    }

}
