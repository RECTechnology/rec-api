<?php

namespace App\Controller\CRUD;

use Symfony\Component\HttpFoundation\Request;

class ConfigurationSettingsController extends CRUDController
{
    /**
     * @return array
     */
    function getCRUDGrants()
    {
        return [
            self::CRUD_SEARCH => self::ROLE_SUPER_ADMIN,
            self::CRUD_INDEX => self::ROLE_PUBLIC,
            self::CRUD_SHOW => self::ROLE_SUPER_ADMIN,
            self::CRUD_EXPORT => self::ROLE_SUPER_ADMIN,
            self::CRUD_CREATE => self::ROLE_SUPER_ADMIN,
            self::CRUD_UPDATE => self::ROLE_SUPER_ADMIN,
            self::CRUD_DELETE => self::ROLE_SUPER_ADMIN,
        ];
    }

    public function indexAction(Request $request, $role)
    {

        if($role === 'public') {
            $request->query->add(array('scope' => 'app'));
            return parent::indexAction($request, $role);
        }

        return parent::indexAction($request, $role);

    }

}