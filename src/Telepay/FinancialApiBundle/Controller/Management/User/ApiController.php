<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\User;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends RestApiController{

    /**
     * @Rest\View
     */
    public function version(Request $request){
        return $this->rest(
            200,
            "Version info got successfully",
            array(
                'build_id'  => $this->container->getParameter('build_id'),
                'version'  => $this->container->getParameter('version')
            )
        );
    }
}