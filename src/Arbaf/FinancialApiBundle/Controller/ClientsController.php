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
 * Class ClientsController
 * @package Arbaf\FinancialApiBundle\Controller
 */
class ClientsController extends FosRestController
{
    private $allowedRole = 'ROLE_API_ADMIN';

    /**
     * This method returns all clients registered in the system.
     *
     * @ApiDoc(
     *   section="Client Management",
     *   description="Returns all clients",
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
    public function indexAction($limit = 10, $offset = 0)
    {
        $userManager = new ApiUserManager($this, $this->allowedRole);
        $entities = $userManager->getAll($limit, $offset);

        $resp = new ApiResponseBuilder(
            200,
            "Clients got successfully",
            array('clients'=>$entities)
        );

        $view = $this->view($resp, 200);

        return $this->handleView($view);
    }

    /**
     * @ApiDoc(
     *   section="Client Management",
     *   description="Returns one client by ID"
     * )
     *
     * @Rest\View
     */
    public function getAction($id)
    {
        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array("id" => $id));

        if (!$user instanceof User) {
            throw new NotFoundHttpException('Client not found');
        }

        return array('client' => $user);
    }


    /**
     * @ApiDoc(
     *   section="Client Management",
     *   description="Creates a new client"
     * )
     *
     * @Rest\View
     */
    public function addAction($name, $email, $password) {
        return array();
    }

    /**
     * @ApiDoc(
     *   section="Client Management",
     *   description="Updates a client"
     * )
     *
     * @Rest\View
     */
    public function editAction($id, $name, $email, $password) {
        return array();
    }

    /**
     * @ApiDoc(
     *   section="Client Management",
     *   description="Removes a client"
     * )
     *
     * @Rest\View
     */
    public function deleteAction($id) {
        return array();
    }
}
