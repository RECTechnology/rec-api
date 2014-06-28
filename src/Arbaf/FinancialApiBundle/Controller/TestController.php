<?php

namespace Arbaf\FinancialApiBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TestController
 * @package Arbaf\FinancialApiBundle\Controller
 */
class TestController extends FosRestController
{
    /**
     * This method returns a test response for improving the connection with the API.
     *
     * @ApiDoc(
     *   section="Testing the API",
     *   description="Returns a test response",
     *     statusCodes={
     *         200="Returned when successful",
     *         403="Returned when the user is not authorized to say hello",
     *         404={
     *           "Returned when the user is not found",
     *           "Returned when something else is not found"
     *         }
     *     }
     * )
     *
     * @Rest\View
     */
    public function plainAction()
    {
        $data=array('test' => 'plain');

        $view = $this->view($data, 200);

        return $this->handleView($view);
    }

    /**
     * This method returns a test response for improving the connection with the API given an authenticated request.
     *
     * @ApiDoc(
     *   section="Testing the API",
     *   description="Returns a test response"
     * )
     *
     * @Rest\View
     */
    public function authAction()
    {
        $data=array('test' => 'auth');

        $view = $this->view($data, 200);

        return $this->handleView($view);
    }
}
