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
        $groupRepository = $this->getDoctrine()->getRepository("ArbafFinancialApiBundle:Group");

        $entities = $groupRepository->findOneBy(array('id'=>$id));

        if(empty($id))
            throw new HttpException(400, "Missing parameter 'id'");

        $resp = new ApiResponseBuilder(
            200,
            "Group got successfully",
            array('group'=>$entities)
        );

        $view = $this->view($resp, 200);

        return $this->handleView($view);
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
            throw new HttpException(400, "Missing parameter 'name'");

        $group = new Group($groupName);
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
