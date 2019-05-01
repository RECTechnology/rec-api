<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 4/18/19
 * Time: 12:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use Doctrine\Common\Annotations\AnnotationException;
use FOS\RestBundle\Controller\Annotations as Rest;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Entity\DelegatedChange;

/**
 * Class DelegatedChangeController
 * @package Telepay\FinancialApiBundle\Controller\Management\Admin
 */
class DelegatedChangeController extends BaseApiController{

    /**
     * @param Request $request
     * @return Response
     * @Rest\View
     */
    public function indexAction(Request $request)
    {
        return parent::indexAction($request);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws AnnotationException
     * @throws ReflectionException
     * @Rest\View
     */
    public function createAction(Request $request)
    {
        return parent::createAction($request);
    }

    /**
     * @param $id
     * @return Response
     * @Rest\View
     */
    public function showAction($id)
    {
        return parent::showAction($id);
    }

    /**
     * @param Request $request
     * @param $id
     * @return Response
     * @throws AnnotationException
     * @throws ReflectionException
     * @Rest\View
     */
    public function updateAction(Request $request, $id)
    {
        if($request->request->has("status"))
            throw new HttpException(400, "status attribute is readonly");
        return parent::updateAction($request, $id);
    }

    /**
     * @param $id
     * @return Response
     * @Rest\View
     */
    public function deleteAction($id)
    {
        /** @var DelegatedChange $dc */
        $dc = $this->getRepository()->find($id);
        if($dc->getStatus() === "draft")
            return parent::deleteAction($id);
        throw new HttpException(412, "Delete delegated changes only allowed when status is 'draft'");
    }


    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:DelegatedChange";
    }

    function getNewEntity()
    {
        return new DelegatedChange();
    }
}