<?php

namespace Telepay\Bundle\ServicesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
/**
 * Class UsersController
 * @package Telepay\Bundle\ServicesBundle\Controller
 * @Route("/dashboard")
 */
class DashboardController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function dashboardAction()
    {
        // $userManager=$this->get("fos_user.user_manager");
        //$users=$userManager->findUsers();
        //return array('users' => $users);
        return array ('users');
    }
}
