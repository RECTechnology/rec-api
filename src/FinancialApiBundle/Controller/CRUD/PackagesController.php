<?php

namespace App\FinancialApiBundle\Controller\CRUD;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PackagesController extends CRUDController
{
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

    public function searchAction(Request $request, $role)
    {
        throw new HttpException(403, 'Method not implemented');
    }

    public function indexAction(Request $request, $role)
    {
        throw new HttpException(403, 'Method not implemented');
    }

    public function showAction($role, $id)
    {
        throw new HttpException(403, 'Method not implemented');
    }

    public function exportAction(Request $request, $role)
    {
        throw new HttpException(403, 'Method not implemented');
    }

    public function createAction(Request $request, $role)
    {
        throw new HttpException(403, 'Method not implemented');
    }

    public function updateAction(Request $request, $role, $id)
    {
        throw new HttpException(403, 'Method not implemented');
    }

    public function deleteAction($role, $id)
    {
        throw new HttpException(403, 'Method not implemented');
    }

}