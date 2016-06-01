<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 2/01/15
 * Time: 23:41
 */

namespace Telepay\FinancialApiBundle\Controller\Management\Manager;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Entity\Group;

class UsersGroupsController extends RestApiController{

    /**
     * @Rest\View
     */
    public function createAction(Request $request, $id){

        $admin = $this->get('security.context')->getToken()->getUser();

        //search company
        $groupsRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:Group");
        $company = $groupsRepository->find($id);

        if(!$company) throw new HttpException(404, "Company not found");

        //check if this user is admin of this group
        if(!$admin->hasGroup($company)) throw new HttpException(409, 'You don\'t have the necesary permissions');

        //check parameters
        if(!$request->request->has('user_id')) throw new HttpException(404, 'Param user_id not found');
        if(!$request->request->has('role')) throw new HttpException(404, 'Param role not found');

        $user_id = $request->request->get('user_id');
        $role = $request->request->get('role');

        $usersRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:User");
        $user = $usersRepository->find($user_id);
        if(!$user) throw new HttpException(404, "User not found");

        if($user->hasGroup($company)) throw new HttpException(409, "User already in group");

        $user->addGroup($company);

        $em = $this->getDoctrine()->getManager();

        $em->persist($company);
        $em->persist($user);
        $em->flush();

        return $this->restV2(201, "ok", "User added successfully");
    }

    /**
     * @Rest\View
     */
    public function deleteAction(Request $request, $user_id, $group_id){

        $admin = $this->get('security.context')->getToken()->getUser();

        $groupsRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:Group");
        $group = $groupsRepository->find($group_id);
        if(!$group) throw new HttpException(404, "Group not found");

        $usersRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:User");
        $user = $usersRepository->find($user_id);
        if(!$user) throw new HttpException(404, "User not found");

        if(!$admin->hasGroup($group)) throw new HttpException(400, 'You don\'t have the necessary permissions');

        $user->removeGroup($group);

        $em = $this->getDoctrine()->getManager();

        $em->persist($user);
        $em->flush();

        return $this->rest(204, "User removed successfully");

    }

}