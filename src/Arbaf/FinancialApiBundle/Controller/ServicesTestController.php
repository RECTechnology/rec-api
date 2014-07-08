<?php

namespace Arbaf\FinancialApiBundle\Controller;

use Arbaf\FinancialApiBundle\Entity\Group;
use Arbaf\FinancialApiBundle\Entity\User;
use Arbaf\FinancialApiBundle\Response\ApiResponseBuilder;
use Doctrine\DBAL\DBALException;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ServicesTestController
 * @package Arbaf\FinancialApiBundle\Controller
 */
class ServicesTestController extends FosRestController
{
    /**
     * This method returns the information attached to the service
     *
     * @ApiDoc(
     *   section="7 - Test Service",
     *   description="Returns test service documentation",
     *   statusCodes={
     *       200="Returned when successful",
     *   }
     * )
     *
     * @Rest\View(statusCode=200)
     */
    public function indexAction() {

        $docu = array(
            'methods' => array(
                'endpoint' => '/services/test',
                'method' => 'post',
                'description' => 'Service for testing purposes',
                'requirements' => array(),
                'parameters' => ''

            ),
        );

        $resp = new ApiResponseBuilder(
            200,
            "Test service",
            array('documentation'=>$docu)
        );

        $view = $this->view($resp, 200);

        return $this->handleView($view);
    }


    /**
     * This method does nothing and returns HTTP 201 CREATED, its only for testing purposes.
     *
     * @ApiDoc(
     *   section="7 - Test Service",
     *   description="Returns HTTP 201 CREATED",
     *   statusCodes={
     *       201="Returned when successful",
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */
    public function doAction() {
        return array();
    }

}
