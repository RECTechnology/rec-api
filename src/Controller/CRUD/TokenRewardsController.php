<?php

namespace App\Controller\CRUD;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class TokenRewardsController
 * @package App\Controller\CRUD
 */
class TokenRewardsController extends CRUDController {

    /**
     * @return array
     */
    function getCRUDGrants()
    {
        $grants = parent::getCRUDGrants();
        $grants[self::CRUD_INDEX] = self::ROLE_SUPER_ADMIN;
        $grants[self::CRUD_SEARCH] = self::ROLE_SUPER_ADMIN;
        $grants[self::CRUD_SHOW] = self::ROLE_SUPER_ADMIN;
        return $grants;
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
