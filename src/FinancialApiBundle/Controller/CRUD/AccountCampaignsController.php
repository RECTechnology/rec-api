<?php

namespace App\FinancialApiBundle\Controller\CRUD;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\AccountChallenge;
use App\FinancialApiBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class AccountCampaignsController
 * @package App\FinancialApiBundle\Controller\CRUD
 */
class AccountCampaignsController extends CRUDController {

    /**
     * @return array
     */
    function getCRUDGrants()
    {
        return [
            self::CRUD_SEARCH => self::ROLE_SUPER_ADMIN,
            self::CRUD_INDEX => self::ROLE_SUPER_ADMIN,
            self::CRUD_SHOW => self::ROLE_SUPER_ADMIN,
            self::CRUD_EXPORT => self::ROLE_SUPER_ADMIN,
            self::CRUD_CREATE => self::ROLE_SUPER_ADMIN,
            self::CRUD_UPDATE => self::ROLE_SUPER_ADMIN,
            self::CRUD_DELETE => self::ROLE_SUPER_ADMIN,
        ];
    }

    public function indexAction(Request $request, $role)
    {
        return parent::indexAction($request, $role);
    }

    public function showAction($role, $id)
    {
        return parent::showAction($role, $id);
    }

    public function searchAction(Request $request, $role)
    {
        return parent::searchAction($request, $role);
    }
}
