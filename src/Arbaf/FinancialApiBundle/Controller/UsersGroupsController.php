<?php

namespace Arbaf\FinancialApiBundle\Controller;

use Arbaf\FinancialApiBundle\Entity\Group;
use Arbaf\FinancialApiBundle\Entity\User;
use Arbaf\FinancialApiBundle\Response\ApiResponseBuilder;
use Doctrine\DBAL\DBALException;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class UsersController
 * @package Arbaf\FinancialApiBundle\Controller
 */
class UsersGroupsController extends FosRestController
{
    /**
     * @ApiDoc(
     *   section="Groups of Users",
     *   description="Adds a group to a user by id",
     *   requirements={
     *      {
     *          "name"="id",
     *          "requirement"="[0-9]+",
     *          "dataType"="integer",
     *          "description"="User id"
     *      },
     *      {
     *          "name"="group_id",
     *          "requirement"="[0-9]+",
     *          "dataType"="integer",
     *          "description"="Group id"
     *      },
     *   }
     * )
     *
     * @Rest\View
     */
    public function addGroupsAction($id) {
        $em = $this->getDoctrine()->getManager();

        $request=$this->get('request_stack')->getCurrentRequest();
        $groupId = $request->get('group_id');
        if(empty($groupId)) throw new HttpException(400, "Missing parameter 'group_id'");

        $userRepository = $this->getDoctrine()->getRepository("ArbafFinancialApiBundle:User");
        $user = $userRepository->findOneBy(array('id'=>$id));

        $groupRepository = $this->getDoctrine()->getRepository("ArbafFinancialApiBundle:Group");
        $group = $groupRepository->findOneBy(array('id'=>$groupId));

        if(!$group || !($group instanceof Group)) throw new HttpException(404, "Group id does not exists");

        if($user->hasGroup($group->getName())) throw new HttpException(409, "Duplicated resource");

        $user->addGroup($group);

        $em->persist($user);
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
            "Group added successfully",
            array('id' => $user->getId())
        );

        $view = $this->view($resp, 201);

        return $this->handleView($view);
    }

    /**
     * @ApiDoc(
     *   section="Groups of Users",
     *   description="Removes a group from a user by id",
     *   requirements={
     *      {
     *          "name"="id",
     *          "requirement"="[0-9]+",
     *          "dataType"="integer",
     *          "description"="User id"
     *      },
     *      {
     *          "name"="group_id",
     *          "requirement"="[0-9]+",
     *          "dataType"="integer",
     *          "description"="Group id"
     *      },
     *   }
     * )
     *
     * @Rest\View
     */
    public function deleteGroupsAction($id, $group_id) {
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        $userRepository = $doctrine->getRepository("ArbafFinancialApiBundle:User");
        $user = $userRepository->findOneBy(array('id'=>$id));

        $groupRepository = $doctrine->getRepository("ArbafFinancialApiBundle:Group");
        $group = $groupRepository->findOneBy(array('id'=>$group_id));

        if(!$group || !($group instanceof Group)) throw new HttpException(404, "Group id does not exists");

        if(!$user->hasGroup($group->getName()))
            throw new HttpException(404, "User is not in the specified Group");

        $user->removeGroup($group);

        $em->persist($user);
        $em->flush();

        $resp = new ApiResponseBuilder(
            204,
            "Group deleted from user successfully",
            array()
        );

        $view = $this->view($resp, 204);

        return $this->handleView($view);
    }

}
