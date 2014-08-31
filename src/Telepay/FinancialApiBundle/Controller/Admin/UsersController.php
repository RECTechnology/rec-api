<?php

namespace Telepay\FinancialApiBundle\Controller\Admin;

use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Entity\User;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UsersController
 * @package Telepay\FinancialApiBundle\Controller\Manager
 */
class UsersController extends \Telepay\FinancialApiBundle\Controller\Manager\UsersController
{
    /**
     * @Rest\View
     */
    public function addRole(Request $request, $id){
        $roleName = $request->get('role');
        $usersRepo = $this->getRepository();
        $user = $usersRepo->findOneBy(array('id'=>$id));
        if(empty($user)) throw new HttpException(404, 'User not found');
        if(empty($roleName)) throw new HttpException(400, "Missing parameter 'role'");
        if($user->hasRole($roleName)) throw new HttpException(409, "User has already the role '$roleName'");

        $user->addRole($roleName);
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);

        try{
            $em->flush();
        } catch(DBALException $e){
            if(preg_match('/SQLSTATE\[23000\]/',$e->getMessage()))
                throw new HttpException(409, "Duplicated resource");
            else
                throw new HttpException(500, "Unknown error occurred when save");
        }

        return $this->handleRestView(201, "Role added successfully", array());

    }

    /**
     * @Rest\View
     */
    public function deleteRole($id, $role){
        $usersRepo = $this->getRepository();
        $user = $usersRepo->findOneBy(array('id'=>$id));

        if(empty($user)) throw new HttpException(404, "User not found");
        if(!$user->hasRole($role)) throw new HttpException(404, "Role not found in specified user");

        $user->removeRole($role);

        $em = $this->getDoctrine()->getManager();

        $em->persist($user);
        $em->flush();

        return $this->handleRestView(
            204,
            "Role deleted from user successfully",
            array()
        );
    }


}
