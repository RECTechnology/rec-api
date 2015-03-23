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
use Telepay\FinancialApiBundle\Entity\User;

class UsersGroupsController extends RestApiController{

    /**
     * @Rest\View
     */
    public function createAction(Request $request, $id){

        $groupsRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:Group");
        $group = $groupsRepository->find($id);
        if(!$group) throw new HttpException(404, "Group not found");

        $usersRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:User");
        $user = $usersRepository->find($request->get('user_id'));
        if(!$user) throw new HttpException(404, "User not found");

        if($user->hasGroup($group)) throw new HttpException(409, "User alredy in group");

        foreach($user->getGroups() as $g){
            $user->removeGroup($g);
        }

        $user->addGroup($group);

        $em = $this->getDoctrine()->getManager();

        $em->persist($group);
        $em->persist($user);
        $em->flush();

        return $this->restV2(201, "ok", "User added successfully");
    }


    /**
     * @Rest\View
     */
    public function deleteAction(Request $request, $user_id, $group_id){

        $groupsRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:Group");
        $group = $groupsRepository->find($group_id);
        if(!$group) throw new HttpException(404, "Group not found");

        $usersRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:User");
        $user = $usersRepository->find($user_id);
        if(!$user) throw new HttpException(404, "User not found");

        $user->removeGroup($group);

        $group_default = $groupsRepository->findOneBy(array('name'=>'Default'));
        if(!$group_default) {
            $group_default=new Group();
            $group_default->setName('Default');
            $request->request->set('roles',array('ROLE_USER'));
        }

        $user->addGroup($group_default);

        $em = $this->getDoctrine()->getManager();

        $em->persist($user);
        $em->persist($group_default);
        $em->flush();

        return $this->rest(204, "User removed successfully");

    }

}