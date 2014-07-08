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
 * Class UsersController
 * @package Arbaf\FinancialApiBundle\Controller
 */
class ServicesController extends FosRestController
{
    /**
     * This method returns all services in the system.
     *
     * @ApiDoc(
     *   section="6 - Services",
     *   description="Returns all services",
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
    public function indexAction() {
        return array();
    }

}
