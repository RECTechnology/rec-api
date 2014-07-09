<?php

namespace Arbaf\FinancialApiBundle\Controller;

use Arbaf\FinancialApiBundle\DependencyInjection\ApiUserManager;
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
 * Class UsersController
 * @package Arbaf\FinancialApiBundle\Controller
 */
class UsersController extends FosRestController
{
    private $allowedRole = 'ROLE_API_USER';
    /**
     * This method returns all users registered in the system.
     *
     * @ApiDoc(
     *   section="User Management",
     *   description="Returns all users",
     *   output={
     *      "class"="Arbaf\FinancialApiBundle\Entity\User",
     *   },
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
        $userManager = new ApiUserManager($this, $this->allowedRole);
        $entities = $userManager->getAll($limit, $offset);

        $resp = new ApiResponseBuilder(
            200,
            "Users got successfully",
            array('users'=>$entities)
        );

        $view = $this->view($resp, 200);

        return $this->handleView($view);

    }

    /**
     * @ApiDoc(
     *   section="User Management",
     *   description="Returns one user by ID"
     * )
     *
     * @Rest\View
     */
    public function getAction($id)
    {
        $groupRepository = $this->getDoctrine()->getRepository("ArbafFinancialApiBundle:User");

        $entities = $groupRepository->findOneBy(array('id'=>$id));

        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");

        $resp = new ApiResponseBuilder(
            200,
            "User got successfully",
            array('user'=>$entities)
        );

        $view = $this->view($resp, 200);

        return $this->handleView($view);
    }


    /**
     * @ApiDoc(
     *   section="User Management",
     *   description="Creates a new user"
     * )
     *
     * @Rest\View
     */
    public function addAction() {
        return array();
    }

    /**
     * @ApiDoc(
     *   section="User Management",
     *   description="Updates a user"
     * )
     *
     * @Rest\View
     */
    public function editAction($id) {
        return array();
    }

    /**
     * @ApiDoc(
     *   section="User Management",
     *   description="Removes a user"
     * )
     *
     * @Rest\View
     */
    public function deleteAction($id) {
        return array();
    }
}
