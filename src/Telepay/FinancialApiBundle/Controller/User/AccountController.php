<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\User;

use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

class AccountController extends RestApiController{

    /**
     * @Rest\View
     */
    public function read(Request $request){
        $user = $this->get('security.context')->getToken()->getUser();
        $resp = $this->buildRestView(200, "Account info got successfully", $user);
        return $this->handleView($resp);
    }

}