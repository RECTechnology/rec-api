<?php

namespace Arbaf\FinancialApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Controller\Annotations as Rest;
use Arbaf\FinancialApiBundle\Entity\User;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class UserController
 * @package Arbaf\FinancialApiBundle\Controller
 */
class UserController extends Controller
{
    /**
     * This method returns all users registered in the system.
     *
     * @ApiDoc(
     *   section="User Management",
     *   description="Returns all users"
     * )
     *
     * @Rest\View(statusCode=200)
     */
    public function indexAction()
    {
        $userManager = $this->get('fos_user.user_manager');
        if($userManager==null) print_r("usermanager is null");
        $users = $userManager->findUsers();
        return array('users' => $users);
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

        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array("id" => $id));

        if (!$user instanceof User) {
            throw new NotFoundHttpException('User not found');
        }

        return array('user' => $user);
    }
}
