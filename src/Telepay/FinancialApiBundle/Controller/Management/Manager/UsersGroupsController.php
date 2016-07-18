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
use Telepay\FinancialApiBundle\Entity\UserGroup;
use Telepay\FinancialApiBundle\Entity\Group;

class UsersGroupsController extends RestApiController{

    /**
     * @Rest\View
     * description: add user to company with user_id or email
     * permissions: ROLE_ADMIN(company)
     */
    public function createAction(Request $request, $id){

        $admin = $this->get('security.context')->getToken()->getUser();

        //search company
        $groupsRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:Group");
        $company = $groupsRepository->find($id);

        if(!$company) throw new HttpException(404, "Company not found");

        $adminRoles = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:UserGroup")->findOneBy(array(
                'user'  =>  $admin->getId(),
                'group' =>  $id)
            );

        //check if this user is admin of this group
        if(!$admin->hasGroup($company->getName()) || !$adminRoles->hasRole('ROLE_ADMIN'))
            throw new HttpException(409, 'You don\'t have the necesary permissions');

        $usersRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:User");

        //check parameters
        if(!$request->request->has('user_id')){
            if(!$request->request->has('email')){
                throw new HttpException(404, 'Param user_id not found');
            }else{
                $user = $usersRepository->findOneBy(array(
                    'email' =>  $request->request->get('email')
                ));
                if(!$user) throw new HttpException(404, "User not found");
            }
        }else{
            $user_id = $request->request->get('user_id');
            $user = $usersRepository->find($user_id);
            if(!$user) throw new HttpException(404, "User not found");
        }

        if(!$request->request->has('role')) throw new HttpException(404, 'Param role not found');

        $role = $request->request->get('role');
        if($role != ''){
            $role_array = $role;
        }else{
            $role_array = array(
                'ROLE_READONLY'
            );
        }

        if($user->hasGroup($company->getName())) throw new HttpException(409, "User already in group");

        $userGroup = new UserGroup();
        $userGroup->setUser($user);
        $userGroup->setGroup($company);
        $userGroup->setRoles($role_array);

        $em = $this->getDoctrine()->getManager();

        $em->persist($userGroup);
        $em->flush();

        //send email
        $url = '';
        $this->_sendEmail('You has beed added to this company', $user->getEmail(), $url, $company->getName());


        return $this->restV2(201, "ok", "User added successfully");
    }

    /**
     * @Rest\View
     * description: remove user from company
     * permissions: ROLE_ADMIN (active company), ROLE_SUPER_ADMIN(all)
     */
    public function deleteAction(Request $request, $user_id, $group_id){

        $admin = $this->get('security.context')->getToken()->getUser();

        if(!$admin->hasRole('ROLE_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');

        $groupsRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:Group");
        $group = $groupsRepository->find($group_id);
        if(!$group) throw new HttpException(404, "Group not found");

        $usersRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:User");
        $user = $usersRepository->find($user_id);
        if(!$user) throw new HttpException(404, "User not found");

        if(!$admin->hasGroup($group) && !$admin->hasRole('ROLE_SUPER_ADMIN')) throw new HttpException(409, 'You don\'t have the necesary permissions');

        $repo = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:UserGroup");
        $entity = $repo->findOneBy(array('user'=>$user_id, 'group'=>$group_id));
        if(empty($entity)) throw new HttpException(404, "Not found");
        $em = $this->getDoctrine()->getManager();
        $em->remove($entity);
        $em->flush();

        //send email
        $url = '';
        $this->_sendEmail('You has beed removed from this company', $user->getEmail(), $url, $group->getName());

        return $this->rest(204, "User removed successfully");

    }

    /**
     * @Rest\View
     * description: add roles to company with array
     * permissions: ROLE_ADMIN (active company)
     */
    public function addRoleAction(Request $request, $user_id, $group_id){

        $admin = $this->get('security.context')->getToken()->getUser();

        $groupsRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:Group");
        $group = $groupsRepository->find($group_id);
        if(!$group) throw new HttpException(404, "Group not found");

        $usersRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:User");
        $user = $usersRepository->find($user_id);
        if(!$user) throw new HttpException(404, "User not found");

        $usersRolesRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:UserGroup");
        $adminRoles = $usersRolesRepository->findOneBy(array(
            'user'   =>  $admin->getId(),
            'group'  =>  $group_id
        ));
        if(!$adminRoles) throw new HttpException(404, "User Roles not found");

        if(!$request->request->has('roles')) throw new HttpException(404, 'Param role not found');
        $role = $request->request->get('roles');

        if(!$adminRoles->hasRole('ROLE_ADMIN')) throw new HttpException(409, 'You don\'t have the necesary permissions');

        $entity = $usersRolesRepository->findOneBy(array('user'=>$user_id, 'group'=>$group_id));
        if(empty($entity)) throw new HttpException(404, "Not found");
        $entity->setRoles($role);
        $em = $this->getDoctrine()->getManager();
        $em->persist($entity);
        $em->flush();

        return $this->rest(204, "User updated successfully");

    }

    /**
     * @Rest\View
     */
    public function deleteRoleAction(Request $request, $user_id, $group_id){

        $admin = $this->get('security.context')->getToken()->getUser();

        $groupsRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:Group");
        $group = $groupsRepository->find($group_id);
        if(!$group) throw new HttpException(404, "Group not found");

        $usersRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:User");
        $user = $usersRepository->find($user_id);
        if(!$user) throw new HttpException(404, "User not found");

        if(!$request->request->has('role')) throw new HttpException(404, 'Param role not found');
        $role = $request->request->get('role');

        if(!$admin->hasGroup($group) && !$admin->hasRole('ROLE_SUPER_ADMIN')) throw new HttpException(409, 'You don\'t have the necesary permissions');

        $repo = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:UserGroup");
        $entity = $repo->findOneBy(array('user'=>$user_id, 'group'=>$group_id));
        if(empty($entity)) throw new HttpException(404, "Not found");
        $entity->removeRole($role);
        $em = $this->getDoctrine()->getManager();
        $em->persist($entity);
        $em->flush();

        return $this->rest(204, "User updated successfully");

    }

    private function _sendEmail($subject, $to, $url, $company){
        $from = 'no-reply@chip-chap.com';
        $template = 'TelepayFinancialApiBundle:Email:changedgroup.html.twig';
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo(array(
                $to
            ))
            ->setBody(
                $this->container->get('templating')
                    ->render($template,
                        array(
                            'company'   =>  $company,
                            'url'       =>  $url,
                            'subject'   =>  $subject
                        )
                    )
            )
            ->setContentType('text/html');

        $this->container->get('mailer')->send($message);
    }

}