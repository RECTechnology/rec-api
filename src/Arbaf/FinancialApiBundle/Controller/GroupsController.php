<?php

namespace Arbaf\FinancialApiBundle\Controller;

use Arbaf\FinancialApiBundle\DependencyInjection\ApiUserManager;
use Arbaf\FinancialApiBundle\Entity\Group;
use Arbaf\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Controller\Annotations as Rest;
use Arbaf\FinancialApiBundle\Entity\User;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class GroupsController
 * @package Arbaf\FinancialApiBundle\Controller
 */
class GroupsController extends FosRestController
{
    /**
     * This method returns all users registered in the system.
     *
     * @ApiDoc(
     *   section="3 - Group Management",
     *   description="Returns all groups",
     *   statusCodes={
     *       200="Returned when successful",
     *   },
     *   filters={
     *      {
     *          "name"="limit",
     *          "data-type"="integer",
     *          "description"="Max numbers of items to get",
     *          "default"="10"
     *      },
     *      {
     *          "name"="offset",
     *          "data-type"="integer",
     *          "description"="Starting item to get",
     *          "default"="0"
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=200)
     */
    public function indexAction($limit = 10, $offset = 0)
    {
        $groupRepository = $this->getDoctrine()->getRepository("ArbafFinancialApiBundle:Group");

        $entities = $groupRepository->findBy(array(), array(), $limit, $offset);

        $resp = new ApiResponseBuilder(
            200,
            "Groups got successfully",
            array('groups'=>$entities)
        );

        $view = $this->view($resp, 200);

        return $this->handleView($view);
    }

    /**
     * @ApiDoc(
     *   section="3 - Group Management",
     *   description="Returns one group by ID"
     * )
     *
     * @Rest\View
     */
    public function getAction($id)
    {
        return array('user' => "ccc");
    }


    /**
     * @ApiDoc(
     *   section="3 - Group Management",
     *   description="Creates a new group",
     *   requirements={
     *      {
     *          "name"="name",
     *          "requirement"="[a-zA-Z0-9]+",
     *          "dataType"="string",
     *          "description"="Name for the group"
     *      }
     *   }
     *
     * )
     *
     * @Rest\View
     */
    public function addAction() {
        $em = $this->getDoctrine()->getManager();

        $request=$this->get('request_stack')->getCurrentRequest();
        $groupName = $request->get('name');
        if(empty($groupName))
            throw new HttpException(400, "Parameter 'name' is required for creating a group");

        $group = new Group($groupName);
        $em->persist($group);
        $em->flush();
        $resp = new ApiResponseBuilder(
            201,
            "Group created successfully",
            array('id' => $group->getId())
        );

        $view = $this->view($resp, 201);

        return $this->handleView($view);
    }

    /**
     * @ApiDoc(
     *   section="2 - User Management",
     *   description="Updates a user"
     * )
     *
     * @Rest\View
     */
    public function editAction($id, $name, $email, $password) {
        return array();
    }

    /**
     * @ApiDoc(
     *   section="3 - Group Management",
     *   description="Removes a group",
     *   requirements={
     *      {
     *          "name"="id",
     *          "requirement"="[0-9]+",
     *          "dataType"="integer",
     *          "description"="Group id"
     *      }
     *   }
     * )
     *
     * @Rest\View
     */
    public function deleteAction($id) {
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();
        $groupRepository = $doctrine->getRepository("ArbafFinancialApiBundle:Group");

        $group = $groupRepository->findOneBy(array('id'=>$id));

        $em->remove($group);
        $em->flush();

        $resp = new ApiResponseBuilder(
            204,
            "Group deleted successfully",
            array()
        );

        $view = $this->view($resp, 204);

        return $this->handleView($view);
    }
}
