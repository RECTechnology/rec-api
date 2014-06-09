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
    function __construct(){
        parent::__construct();
        $this->userManager = $this->get("fos_user.user_manager");
    }
    /**
     * This is the documentation description of your method, it will appear
     * on a specific pane. It will read all the text until the first
     * annotation.
     *
     * @ApiDoc(
     *   section="/users",
     *   description="Returns all users"
     * )
     *
     * @Rest\View
     */
    public function allAction()
    {
        $users = $this->userManager->findUsers();
        return array('users' => $users);
    }

    /**
     * @ApiDoc(
     *   section="/users",
     *   description="Returns one user given its ID"
     * )
     *
     * @Rest\View
     */
    public function getAction($id)
    {
        $user = $this->userManager->findUserBy(array("id" => $id));

        if (!$user instanceof User) {
            throw new NotFoundHttpException('User not found');
        }

        return array('user' => $user);
    }
}
