<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 4/18/19
 * Time: 12:33 PM
 */

namespace App\Controller\Management\Admin;

use Doctrine\Common\Annotations\AnnotationException;
use FOS\RestBundle\Controller\Annotations as Rest;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Controller\BaseApiController;
use App\Entity\DelegatedChange;

/**
 * Class DelegatedChangeController
 * @package App\Controller\Management\Admin
 */
class DelegatedChangeController extends BaseApiController {

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
        if($request->request->has("status")){
            $newStatus = $request->request->get("status");
            /** @var DelegatedChange $dc */
            $dc = $this->findObject($id);
            foreach (DelegatedChange::ALLOWED_STATUS_CHANGES as $statusPack){
                if(($dc->getStatus() === $statusPack['old']) && ($newStatus === $statusPack['new'])){
                    return parent::updateAction($request, $id);
                }
            }
            // if status is going to be changed, throw error
            throw new HttpException(400, "status attribute is readonly");
        }
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


    public function getRepositoryName()
    {
        return "FinancialApiBundle:DelegatedChange";
    }

    function getNewEntity()
    {
        return new DelegatedChange();
    }
}