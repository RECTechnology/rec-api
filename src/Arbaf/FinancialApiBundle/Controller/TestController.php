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
     *   statusCodes={
     *       200="Returned when successful",
     *       404="Returned when the resource is does not exists"
     *   }
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
     *   description="Returns a test response",
     *   statusCodes={
     *       200="Returned when successful",
     *       401="Returned when the user is not authenticated",
     *       403="Returned when the user is not authorized",
     *       404="Returned when the resource is does not exists"
     *   },
     *   requirements={
     *      {
     *          "name"="access-key",
     *          "dataType"="string",
     *          "requirement"="[a-zA-Z0-9]+",
     *          "description"="User api key"
     *      },
     *      {
     *          "name"="nonce",
     *          "dataType"="string",
     *          "requirement"="[a-zA-Z0-9]+",
     *          "description"="Once used number, this number must be different for every request"
     *      },
     *      {
     *          "name"="timestamp",
     *          "dataType"="integer",
     *          "requirement"="[0-9]+",
     *          "description"="Timestamp of the request"
     *      },
     *      {
     *          "name"="algorithm",
     *          "dataType"="string",
     *          "requirement"="(SHA256|MD5)",
     *          "description"="Algorithm used to make the signature"
     *      },
     *      {
     *          "name"="signature",
     *          "dataType"="string",
     *          "requirement"="[a-zA-Z0-9]+",
     *          "description"="Requirements (access-key+nonce+timestamp) encrypted with the given algorithm using the access-secret as encryption key."
     *      }
     *   }
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
