<?php

namespace App\FinancialApiBundle\Controller\CRUD;

use App\FinancialApiBundle\Entity\TreasureWithdrawalValidation;
use App\FinancialApiBundle\Exception\AppException;
use Doctrine\Common\Annotations\AnnotationException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TreasureWithdrawalValidationsController
 * @package App\FinancialApiBundle\Controller\CRUD
 */
class TreasureWithdrawalValidationsController extends CRUDController {

    /**
     * @return array
     */
    function getCRUDGrants()
    {
        $grants = parent::getCRUDGrants();
        $grants[self::CRUD_SEARCH] = self::ROLE_SUPER_USER;
        $grants[self::CRUD_INDEX] = self::ROLE_SUPER_USER;
        $grants[self::CRUD_SHOW] = self::ROLE_SUPER_USER;
        $grants[self::CRUD_CREATE] = self::ROLE_SUPER_USER;
        $grants[self::CRUD_UPDATE] = self::ROLE_PUBLIC;
        return $grants;
    }

    /**
     * @param Request $request
     * @param $id
     * @return array
     * @throws AnnotationException
     */
    function update(Request $request, $id)
    {
        /** @var TreasureWithdrawalValidation $validation */
        $validation = $this->getRepository()->find($id);
        if($validation->getToken() != $request->request->get('token')){
            throw new AppException(400, "Invalid token");
        }
        $req = new Request([], ['status' => TreasureWithdrawalValidation::STATUS_APPROVED]);
        $req->setMethod('PUT');

        return parent::update($req, $id);
    }
}
