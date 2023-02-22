<?php

namespace App\Controller\CRUD;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PosController extends CRUDController
{
    function indexAction(Request $request, $role)
    {
        if($role === 'public' || $role === 'self' || $role === 'manager')
            throw new HttpException(403, "Method not implemented");

        $user = $this->getUser();
        $account = $user->getActiveGroup();

        $request->query->add(array('account_id'=> $account->getId()));
        return parent::indexAction($request, $role);
    }

}