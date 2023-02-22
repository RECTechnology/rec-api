<?php

namespace App\Controller\CRUD;

use Symfony\Component\HttpFoundation\Request;

class BadgesController extends CRUDController
{
    /**
     * @return array
     */
    function getCRUDGrants()
    {
        return [
            self::CRUD_SEARCH => self::ROLE_SUPER_ADMIN,
            self::CRUD_INDEX => self::ROLE_PUBLIC,
            self::CRUD_SHOW => self::ROLE_PUBLIC,
            self::CRUD_EXPORT => self::ROLE_SUPER_ADMIN,
            self::CRUD_CREATE => self::ROLE_SUPER_ADMIN,
            self::CRUD_UPDATE => self::ROLE_SUPER_ADMIN,
            self::CRUD_DELETE => self::ROLE_SUPER_ADMIN,
        ];
    }

    public function searchAction(Request $request, $role)
    {
        return parent::searchAction($request, $role);
    }

    public function indexAction(Request $request, $role)
    {
        return parent::indexAction($request, $role);
    }

    public function showAction($role, $id)
    {
        return parent::showAction($role, $id);
    }

    public function exportAction(Request $request, $role)
    {
        return parent::exportAction($request, $role);
    }
}