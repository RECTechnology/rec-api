<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Entity\Client;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ClientController
 * @package Telepay\FinancialApiBundle\Controller\Management\Admin
 */
class ClientsController extends BaseApiController {

    function getRepositoryName() {
        return 'TelepayFinancialApiBundle:Client';
    }

    function getNewEntity() {
        return new Client();
    }

    /**
     * @Rest\View
     */
    public function indexAction(Request $request){
        return parent::indexAction($request);
    }

    /**
     * @Rest\View
     */
    public function showAction($id){
        return parent::showAction($id);
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id){
        return parent::updateAction($request, $id);
    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        return parent::deleteAction($id);
    }


}
