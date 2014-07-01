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
     *   section="2 - User Management",
     *   description="Returns all users",
     *   statusCodes={
     *       200="Returned when successful",
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
     *   section="2 - User Management",
     *   description="Returns one user by ID"
     * )
     *
     * @Rest\View
     */
    public function getAction($id)
    {
        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array("id" => $id));

        if (!$user instanceof User) {
            throw new NotFoundHttpException('User not found');
        }

        return array('user' => $user);
    }


    /**
     * @ApiDoc(
     *   section="2 - User Management",
     *   description="Creates a new user"
     * )
     *
     * @Rest\View
     */
    public function addAction($name, $email, $password) {
        return array();
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
     *   section="2 - User Management",
     *   description="Removes a user"
     * )
     *
     * @Rest\View
     */
    public function deleteAction($id) {
        return array();
    }
}
