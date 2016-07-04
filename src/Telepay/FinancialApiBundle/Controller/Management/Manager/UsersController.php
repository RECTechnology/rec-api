<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Manager;

use Doctrine\DBAL\DBALException;
use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\LimitCount;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use Telepay\FinancialApiBundle\Entity\User;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\UserGroup;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

/**
 * Class UsersController
 * @package Telepay\FinancialApiBundle\Controller\Manager
 */
class UsersController extends BaseApiController
{
    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:User";
    }

    function getNewEntity()
    {
        return new User();
    }

    /**
     * @Rest\View
     */
    public function su(Request $request, $id){

        $em = $this->getDoctrine()->getManager();
        $usersRepo = $em->getRepository("TelepayFinancialApiBundle:User");
        $tokensRepo = $em->getRepository("TelepayFinancialApiBundle:AccessToken");

        $user = $usersRepo->findOneBy(array('id'=>$id));

        $token = $tokensRepo->findOneBy(
            array('token'=>$this->get('security.context')->getToken()->getToken())
        );

        $token->setUser($user);
        //$token->setAuthenticated(true);

        $em->persist($token);
        $token2 = $tokensRepo->findOneBy(
            array('token'=>$this->get('security.context')->getToken()->getToken())
        );

        return $this->rest(
            200,
            "yeagh accesstokensss",
            $token2
        );
        //update access_token -> user with id $id
    }

    /**
     * @Rest\View
     * Permissions: ROLE_SUPER_ADMIN
     */
    public function indexAction(Request $request){

        //TODO only superadmin can access here
        $securityContext = $this->get('security.context');

        if(!$securityContext->isGranted('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        $userRepo = $this->getDoctrine()->getRepository('TelepayFinancialApiBundle:User');
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder('TelepayFinancialApiBundle:User');

        if($request->query->get('query') != ''){
            $query = $request->query->get('query');
            $search = $query['search'];
            $order = $query['order'];
            $dir = $query['dir'];
        }else{
            $search = '';
            $order = 'id';
            $dir = 'DESC';
        }

        $userQuery = $userRepo->createQueryBuilder('p')
            ->orderBy('p.'.$order, $dir)
            ->where($qb->expr()->orX(
                $qb->expr()->like('p.username', $qb->expr()->literal('%'.$search.'%')),
                $qb->expr()->like('p.id', $qb->expr()->literal('%'.$search.'%')),
                $qb->expr()->like('p.email', $qb->expr()->literal('%'.$search.'%')),
                $qb->expr()->like('p.name', $qb->expr()->literal('%'.$search.'%'))
            ))
            ->getQuery();

        $all = $userQuery->getResult();

        $filtered = $all;

        $total = count($filtered);

        $entities = array_slice($filtered, $offset, $limit);

        array_map(function($elem){
            $elem->setAccessToken(null);
            $elem->setRefreshToken(null);
            $elem->setAuthCode(null);
            $groups = array();
            $list_groups = $elem->getGroups();
            foreach($list_groups as $group){
                $groups[] = $group->getName();
            }
            $elem->setGroupData($groups);
        }, $entities);

        return $this->rest(
            200,
            "Request successful",
            array(
                'total' => $total,
                'start' => intval($offset),
                'end' => count($entities)+$offset,
                'elements' => $entities
            )
        );
    }

    /**
     * @Rest\View
     * Permissions: ROLE_READONLY(active_group), ROLE_SUPER_ADMIN(all)
     */
    public function indexByGroup(Request $request, $id){

        //Only the superadmin can access here
        $admin = $this->get('security.context')->getToken()->getUser();
        $adminGroup = $admin->getActiveGroup();

        if(!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN') && $adminGroup->getId() != $id)
            throw new HttpException(403,'You don\'t have the necessary permissions');

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        $current_group = $this->getDoctrine()->getRepository('TelepayFinancialApiBundle:Group')->find($id);
        if(!$current_group) throw new HttpException(404, 'Group not found');

        $all = $this->getRepository()->findAll();

        $filtered = [];
        foreach($all as $user){
            if(count($user->getGroups()) >= 1){
                $groups = $user->getGroups();
                foreach($groups as $group){
                    if($group->getId() == $id){
                        $user->setRoles($user->getRolesCompany($group->getId()));
                        $filtered []= $user;
                    }
                }

            }

        }

        $total = count($filtered);

        $entities = array_slice($filtered, $offset, $limit);
        array_map(function($elem){
            $elem->setAccessToken(null);
            $elem->setRefreshToken(null);
            $elem->setAuthCode(null);
        }, $entities);

        return $this->rest(
            200,
            "Request successful",
            array(
                'total' => $total,
                'start' => intval($offset),
                'end' => count($entities)+$offset,
                'elements' => $entities
            )
        );
    }

    /**
     * @Rest\View
     */
    public function createAction(Request $request){

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        $usersRepo = $em->getRepository("TelepayFinancialApiBundle:User");
        $groupsRepo = $em->getRepository("TelepayFinancialApiBundle:Group");

        $role_array = array();
        if($request->request->has('group_id')){
            if($request->request->has('role')) throw new HttpException(404, 'Parameter Role not found');
            $role_array[] = $request->request->get('role');
            $groupId = $request->request->get('group_id');
            $request->request->remove('group_id');
            $group = $groupsRepo->find($groupId);

            if(!$group) throw new HttpException(404, 'Group not found');

            //TODO check if the user is admin in this group or is superadmin
            if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
                if(!$admin->hasGroup($group->getName())) throw new HttpException(403, 'You don\'t have the necessary permissions to add a user in this group');
            }

            $request->request->add(array(
                'active_group'  =>  $group
            ));
        }else{
            $request->request->add(array(
                'active_group'  =>  $admin->getActiveGroup()
            ));
        }

        if(!$request->request->has('password'))
            throw new HttpException(400, "Missing parameter 'password'");
        if(!$request->request->has('repassword'))
            throw new HttpException(400, "Missing parameter 'repassword'");
        $password = $request->get('password');
        $repassword = $request->get('repassword');
        if($password != $repassword) throw new HttpException(400, "Password and repassword are differents.");
        $request->request->remove('password');
        $request->request->remove('repassword');

        if(!$request->request->has('phone')) $request->request->add(array('phone'=>''));
        if(!$request->request->has('prefix')) $request->request->add(array('prefix'=>''));

        $request->request->add(array('plain_password'=>$password));
        $request->request->add(array('enabled'=>1));
        $request->request->add(array('base64_image'=>''));
//        $request->request->add(array('default_currency'=>'EUR'));
        $request->request->add(array('gcm_group_key'=>''));
        //TODO add_role user by default

        $resp= parent::createAction($request);

        if($resp->getStatusCode() == 201){

            $user_id = $resp->getContent();
            $user_id = json_decode($user_id);
            $user_id = $user_id->data;
            $user_id = $user_id->id;
            $user = $usersRepo->findOneBy(array('id'=>$user_id));

            $userGroup = new UserGroup();
            $userGroup->setUser($user);
            $userGroup->setGroup($group);
            $userGroup->setRoles($role_array);

            $em = $this->getDoctrine()->getManager();

            $em->persist($userGroup);
            $em->flush();
        }

        return $resp;

    }

    /**
     * @Rest\View
     * permissions: ROLE_SUPER_ADMIN(all), ROLE_READ_ONLY(must be member of this group)
     */
    public function showAction($id){
        //check if the user is admin f this group or pertence to this group
        $user = $this->get('security.context')->getToken()->getUser();

        $activeGroup = $user->getActiveGroup();

        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");

        $repo = $this->getRepository();

        $entities = $repo->findOneBy(array('id'=>$id));

        if(empty($entities)) throw new HttpException(404, "Not found");

        if(!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN') && $activeGroup->getId() != $id) throw new HttpException(403, 'You don\'t have the necessary permissions');

        $entities->setAccessToken(null);
        $entities->setRefreshToken(null);
        $entities->setAuthCode(null);

        $group_data = array();
        $group_data['id'] = $activeGroup->getId();
        $group_data['name'] = $activeGroup->getName();
        $group_data['admin'] = $activeGroup->getCreator()->getName();
        $group_data['email'] = $activeGroup->getCreator()->getEmail();

        $entities->setGroupData($group_data);

        return $this->rest(200, "Request successful", $entities);
    }

    /**
     * @Rest\View
     * permissions: ROLE_SUPER_ADMIN
     */
    public function setImage(Request $request, $id){
        $admin = $this->get('security.context')->getToken()->getUser();

        if(!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');
        $activeGroup = $admin->getActiveGroup();

        if($id == "") throw new HttpException(400, "Missing parameter 'id'");

        if($id == 0){
            $username = $request->get('username');
            if($username==""){
                $id = $this->get('security.context')->getToken()->getUser()->getId();
            }
            else {
                $repo = $this->getRepository();
                $user = $repo->findOneBy(array('username' => $username));
                if (empty($user)) throw new HttpException(404, 'User not found');
                $id = $user->getId();
            }
        }

        if($request->request->has('base64_image')) $base64Image = $request->request->get('base64_image');
        else throw new HttpException(400, "Missing parameter 'base64_image'");


        $image = base64_decode($base64Image);

        try {
            imagecreatefromstring($image);
        }catch (Exception $e){
            throw new HttpException(400, "Invalid parameter 'base64_image'");
        }

        $repo = $this->getRepository();

        $user = $repo->findOneBy(array('id'=>$id));

        if(empty($user)) throw new HttpException(404, "User Not found");

        if(!$user->hasGroup($activeGroup->getName())) throw new HttpException(403, 'You don\'t have the necessary permissions');

        $user->setBase64Image($base64Image);

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);

        try{
            $em->flush();
            return $this->rest(
                204,
                "Image changed successfully"
            );
        } catch(DBALException $e){
            if(preg_match('/SQLSTATE\[23000\]/',$e->getMessage()))
                throw new HttpException(409, "Duplicated resource");
            else
                throw new HttpException(500, "Unknown error occurred when save");
        }


    }

    /**
     * @Rest\View
     * permissions: ROLE_SUPER_ADMIN
     */
    public function updateAction(Request $request, $id){

        //TODO check if this admin is admin of this user
        $user = $this->get('security.context')->getToken()->getUser();

        if(!$user->hasRole('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');

        $role_commerce = null;
        if($request->request->has('roles')){
            $roles = $request->request->get('roles');
            if(in_array('ROLE_COMMERCE', $roles)){
                $role_commerce = true;
            }
        }

        if($request->request->has('password')){
            if($request->request->has('repassword')){
                $password = $request->get('password');
                $repassword = $request->get('repassword');
                if($password != $repassword) throw new HttpException(400, "Password and repassword are differents.");
                $userManager = $this->container->get('access_key.security.user_provider');
                $user = $userManager->loadUserById($id);
                $user->setPlainPassword($request->get('password'));
                $userManager->updatePassword($user);
                $request->request->remove('password');
                $request->request->remove('repassword');
            }else{
                throw new HttpException(400,"Missing parameter 'repassword'");
            }

        }

        $resp = parent::updateAction($request, $id);
        if($resp->getStatusCode() == 204){

            if($role_commerce !== null){
                //TODO check if the admins have POS FEES for all the admins
            }

        }
        return $resp;
    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        if($this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');
        return parent::deleteAction($id);
    }

    /**
     * @Rest\View
     */
    public function deleteByNameAction($username){
        if($this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');
        $repo = $this->getRepository();
        $user = $repo->findOneBy(array('username'=>$username));
        if(empty($user)) throw new HttpException(404, 'User not found');
        $idUser = $user->getId();
        return parent::deleteAction($idUser);
    }

//    private function _setMethods(Request $request, $id){
//        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");
//        $usersRepo = $this->getRepository();
//        $user = $usersRepo->findOneBy(array('id'=>$id));
//        $listMethods = $user->getMethodsList();
//
//        $putMethods = $request->get('methods');
//        foreach($putMethods as $method){
//            if(!in_array($method, $listMethods)){
//                $this->_addMethod($id, $method);
//            }
//        }
//        return $this->rest(204, "Edited");
//    }

//    private function _addMethod($id, $cname){
//        $usersRepo = $this->getRepository();
//        $methodsRepo = $this->get('net.telepay.method_provider');
//        $user = $usersRepo->findOneBy(array('id'=>$id));
//        $method = $methodsRepo->findByCname($cname);
//        if(empty($user)) throw new HttpException(404, 'User not found');
//        if(empty($method)) throw new HttpException(404, 'Method not found');
//
//        $user->addMethod($cname);
//        $em = $this->getDoctrine()->getManager();
//        $limitRepo = $em->getRepository("TelepayFinancialApiBundle:LimitCount");
//        $limit = $limitRepo->findOneBy(array('cname' => $cname, 'user' => $user));
//        if(!$limit){
//            $limit = new LimitCount();
//            $limit->setUser($user);
//            $limit->setCname($cname);
//            $limit->setSingle(0);
//            $limit->setDay(0);
//            $limit->setWeek(0);
//            $limit->setMonth(0);
//            $limit->setYear(0);
//            $limit->setTotal(0);
//            $em->persist($limit);
//        }
//
//        try{
//            $em->flush();
//        } catch(DBALException $e){
//            if(preg_match('/SQLSTATE\[23000\]/',$e->getMessage()))
//                throw new HttpException(409, "Duplicated resource");
//            else
//                throw new HttpException(500, "Unknown error occurred when save");
//        }
//    }

//    private function _deleteService($id, $cname){
//        $usersRepo = $this->getRepository();
//        $service = $this->get('net.telepay.service_provider')->findByCname($cname);
//        $user = $usersRepo->findOneBy(array('id'=>$id));
//        if(empty($user)) throw new HttpException(404, "User not found");
//        if(empty($service)) throw new HttpException(404, 'Service not found');
//
//        $user->removeService($cname);
//        $em = $this->getDoctrine()->getManager();
//        $em->persist($user);
//        $em->flush();
//    }


    public function _setRole(Request $request, $id){
        $roleName = $request->get('role');

        if(empty($roleName))
            throw new HttpException(400, "Missing parameter 'role'");
        if($roleName != 'ROLE_SUPER_ADMIN' and $roleName != 'ROLE_ADMIN' and $roleName != 'ROLE_USER'){
            throw new HttpException(404, 'Role not found');
        }

        $usersRepo = $this->getRepository();
        $user = $usersRepo->findOneBy(array('id'=>$id));
        if(empty($user))
            throw new HttpException(404, 'User not found');

        $user->removeRole('ROLE_SUPER_ADMIN');
        $user->removeRole('ROLE_ADMIN');

        if($roleName == 'ROLE_SUPER_ADMIN' or $roleName == 'ROLE_ADMIN')
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

    }

    public function _addBalance($user_id, $currency, $amount){

        $em = $this->getDoctrine()->getManager();

        $usersRepo = $this->getRepository();
        $user = $usersRepo->find($user_id);

        if($currency == 'default') $currency=$user->getDefaultCurrency();

        $currency = strtoupper($currency);
        $wallets=$user->getWallets();

        $find_wallet = 0;
        foreach ( $wallets as $wallet ){
            if ($wallet->getCurrency() == $currency ){

                $wallet->setAvailable( $wallet->getAvailable() + $amount );
                $wallet->setBalance( $wallet->getBalance() + $amount );

                //create transaction
                $transaction = new Transaction();
                $transaction->setStatus('success');
                $transaction->setScale($wallet->getScale());
                $transaction->setCurrency($wallet->getCurrency());
                $transaction->setIp('');
                $transaction->setVersion('');
                $transaction->setService('cash_in');
                $transaction->setVariableFee(0);
                $transaction->setFixedFee(0);
                $transaction->setAmount($amount);
                $transaction->setTotal($amount);
                $transaction->setNotified(true);
                $transaction->setCreated(new \MongoDate());
                $transaction->setUpdated(new \MongoDate());
                $transaction->setUser($user_id);
                $transaction->setDataIn(array(
                    'currency'  =>  $currency,
                    'amount'    =>  $amount,
                    'user_id'   =>  $user_id,
                    'description'   =>  'add balance'
                ));

                $dm = $this->get('doctrine_mongodb')->getmanager();
                $dm->persist($transaction);
                $dm->flush();

                $balancer = $this->get('net.telepay.commons.balance_manipulator');
                $balancer->addBalance($user, $amount, $transaction);

                $find_wallet = 1;
                $em->persist($wallet);
                $em->flush();
            }
        }

        if($find_wallet==0) throw new HttpException(400,'Wallet not found');

        return true;

    }

}
