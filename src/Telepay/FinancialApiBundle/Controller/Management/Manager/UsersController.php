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
     */
    public function indexAction(Request $request){

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        $securityContext = $this->get('security.context');

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

        if(!$securityContext->isGranted('ROLE_SUPER_ADMIN')){
            $filtered = [];
            foreach($all as $user){
                if(!$user->hasRole('ROLE_SUPER_ADMIN'))
                    $filtered []= $user;
            }
        }
        else{
            $filtered = $all;
        }
        $total = count($filtered);

        $entities = array_slice($filtered, $offset, $limit);
        array_map(function($elem){
            $elem->setAllowedServices($this->get('net.telepay.service_provider')->findByCNames($elem->getServicesList()));
            $elem->setAllowedMethods($this->get('net.telepay.method_provider')->findByCNames($elem->getMethodsList()));
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
    public function indexByGroup(Request $request, $id){

        $admin = $this->get('security.context')->getToken()->getUser();

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        $current_group = $this->getDoctrine()->getRepository('TelepayFinancialApiBundle:Group')->find($id);
        if(!$current_group) throw new HttpException(404, 'Group not found');

        if($current_group->getCreator()->getId() != $admin->getId()) throw new HttpException(403, 'You don\'t have the necessary permissions');

        $all = $this->getRepository()->findAll();

        $filtered = [];
        foreach($all as $user){
            if(count($user->getGroups()) >= 1){
                $groups = $user->getGroups();
                foreach($groups as $group){
                    if($group->getId() == $id){
                        if(!$user->hasRole('ROLE_SUPER_ADMIN')){
                            $filtered []= $user;
                        }

                    }
                }

            }

        }

        $total = count($filtered);

        $entities = array_slice($filtered, $offset, $limit);
        array_map(function($elem){
            if($elem->getServicesList() == null){
                $elem->setAllowedServices($this->get('net.telepay.service_provider')->findByCNames(array('echo')));
                $elem->setAllowedMethods($this->get('net.telepay.method_provider')->findByCNames(array('echo-in')));
            }else{
                $elem->setAllowedServices($this->get('net.telepay.service_provider')->findByCNames($elem->getServicesList()));
                $elem->setAllowedMethods($this->get('net.telepay.method_provider')->findByCNames($elem->getMethodsList()));
            }
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

        if($request->request->has('group_id')){
            $groupId = $request->request->get('group_id');
            $request->request->remove('group_id');
        }else{
            $groupId = $this->container->getParameter('id_group_default');
        }

        $group = $groupsRepo->find($groupId);
        if(!$group) throw new HttpException(404, 'Group not found');
        if($group->getCreator()->getId() != $admin->getId()) throw new HttpException(403, 'You don\'t have the necessary permissions to add a user in this group');

        if(!$request->request->has('password'))
            throw new HttpException(400, "Missing parameter 'password'");
        if(!$request->request->has('repassword'))
            throw new HttpException(400, "Missing parameter 'repassword'");
        $password = $request->get('password');
        $repassword = $request->get('repassword');
        if($password!=$repassword) throw new HttpException(400, "Password and repassword are differents.");
        $request->request->remove('password');
        $request->request->remove('repassword');

        if(!$request->request->has('phone')) $request->request->add(array('phone'=>''));
        if(!$request->request->has('prefix')) $request->request->add(array('prefix'=>''));

        $request->request->add(array('plain_password'=>$password));
        $request->request->add(array('enabled'=>1));
        $request->request->add(array('base64_image'=>''));
        $request->request->add(array('default_currency'=>'EUR'));
        $request->request->add(array('gcm_group_key'=>''));
        $request->request->add(array('services_list'=>array('sample')));

        $resp= parent::createAction($request);

        if($resp->getStatusCode() == 201){

            if(!$group){
                $group = new Group();
                $group->setName('Default');
                $group->setRoles(array('ROLE_USER'));
                $group->setCreator($this->getUser());
                $em->persist($group);
                $em->flush();
                $servicesRepo = $this->get('net.telepay.service_provider');
                $services = $servicesRepo->findAll();

                foreach($services as $service){
                    $limit_def = new LimitDefinition();
                    $limit_def->setCname($service->getCname());
                    $limit_def->setSingle(0);
                    $limit_def->setDay(0);
                    $limit_def->setWeek(0);
                    $limit_def->setMonth(0);
                    $limit_def->setYear(0);
                    $limit_def->setTotal(0);
                    $limit_def->setGroup($group);
                    $commission = new ServiceFee();
                    $commission->setGroup($group);
                    $commission->setFixed(0);
                    $commission->setVariable(0);
                    $commission->setServiceName($service->getCname());
                    $em->persist($commission);
                    $em->persist($limit_def);

                }
                $em->flush();
            }

            $user_id = $resp->getContent();
            $user_id = json_decode($user_id);
            $user_id = $user_id->data;
            $user_id = $user_id->id;
            $user = $usersRepo->findOneBy(array('id'=>$user_id));

            $currencies=Currency::$LISTA;

            foreach($currencies as $currency){
                $user_wallet = new UserWallet();
                $user_wallet->setBalance(0);
                $user_wallet->setAvailable(0);
                $user_wallet->setCurrency($currency);
                $user_wallet->setUser($user);
                $em->persist($user_wallet);
            }

            $user->addGroup($group);

            $em->persist($user);
            $em->flush();
        }

        return $resp;


    }

    /**
     * @Rest\View
     */
    public function showAction($id){
        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");

        $repo = $this->getRepository();

        $entities = $repo->findOneBy(array('id'=>$id));

        if(empty($entities)) throw new HttpException(404, "Not found");

        $entities->setAllowedServices($this->get('net.telepay.service_provider')->findByCNames($entities->getServicesList()));
        $entities->setAllowedMethods($this->get('net.telepay.method_provider')->findByCNames($entities->getMethodsList()));
        $entities->setAccessToken(null);
        $entities->setRefreshToken(null);
        $entities->setAuthCode(null);

        $group = $entities->getGroups()[0];

        $group_data = array();
        $group_data['id'] = $group->getId();
        $group_data['name'] = $group->getName();
        $group_data['admin'] = $group->getCreator()->getName();
        $group_data['email'] = $group->getCreator()->getEmail();

        $entities->setGroupData($group_data);

        return $this->rest(200, "Request successful", $entities);
    }

    /**
     * @Rest\View
     */
    public function setImage(Request $request, $id){
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

        /*
        try {
            imagecreatefromstring($image);
        }catch (Exception $e){
            throw new HttpException(400, "Invalid parameter 'base64_image'");
        }
        */

        $repo = $this->getRepository();

        $user = $repo->findOneBy(array('id'=>$id));

        if(empty($user)) throw new HttpException(404, "User Not found");

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
     */
    public function updateAction(Request $request, $id){
        if(empty($id) && $id !=0) throw new HttpException(400, "Missing parameter 'id'");

        if($id == 0){
            $username = $request->get('username');
            $repo = $this->getRepository();
            $user = $repo->findOneBy(array('username'=>$username));
            if(empty($user)) throw new HttpException(404, 'User not found');
            $id = $user->getId();
            $request->request->remove('username');
        }

        $services = null;
        if($request->request->has('services')){
            $services = $request->get('services');
            $request->request->remove('services');
            $request->request->add(array('services_list' =>$services));
        }

        $methods = null;
        if($request->request->has('methods')){
            $services = $request->get('methods');
            $request->request->remove('methods');
            $request->request->add(array('methods_list' =>$methods));
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
        $balance=null;
        if($request->request->has('addBalance')){
            $balance = $request->get('addBalance');
            $request->request->remove('addBalance');
            $currency = 'default';
            if($request->request->has('currency')){
                $currency = $request->request->get('currency');
                $request->request->remove('currency');
            }
            $adder = $this->_addBalance( $id, $currency, $balance );
        }
        $resp = parent::updateAction($request, $id);
        if($resp->getStatusCode() == 204){
            if($services !== null){
                $request->request->add(array('services'=>$services));
                $this->_setServices($request, $id);
            }
            if($methods !== null){
                $request->request->add(array('methods'=>$methods));
                $this->_setMethods($request, $id);
            }

        }
        return $resp;
    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        return parent::deleteAction($id);
    }

    /**
     * @Rest\View
     */
    public function deleteByNameAction($username){
        $repo = $this->getRepository();
        $user = $repo->findOneBy(array('username'=>$username));
        if(empty($user)) throw new HttpException(404, 'User not found');
        $idUser = $user->getId();
        return parent::deleteAction($idUser);
    }


    private function _setServices(Request $request, $id){
        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");
        $usersRepo = $this->getRepository();
        $user = $usersRepo->findOneBy(array('id'=>$id));
        $listServices = $user->getServicesList();

        $putServices = $request->get('services');
        foreach($putServices as $service){
            if(!in_array($service, $listServices)){
                $this->_addService($id, $service);
            }
        }
        return $this->rest(204, "Edited");
    }

    private function _setMethods(Request $request, $id){
        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");
        $usersRepo = $this->getRepository();
        $user = $usersRepo->findOneBy(array('id'=>$id));
        $listMethods = $user->getMethodsList();

        $putMethods = $request->get('methods');
        foreach($putMethods as $method){
            if(!in_array($method, $listMethods)){
                $this->_addMethod($id, $method);
            }
        }
        return $this->rest(204, "Edited");
    }


    /**
     * @Rest\View
     */
    public function addService(Request $request, $id){
        $serviceId = $request->get('service_id');
        if(empty($serviceId)) throw new HttpException(400, "Missing parameter 'service_id'");
        $this->_addService($id, $serviceId);
        return $this->rest(201, "Service added successfully", array());
    }

    /**
     * @Rest\View
     */
    public function deleteService($id, $service_id){
        $this->_deleteService($id, $service_id);
        return $this->rest(204,"Service deleted from user successfully");
    }


    private function _addService($id, $cname){
        $usersRepo = $this->getRepository();
        $servicesRepo = $this->get('net.telepay.service_provider');
        $user = $usersRepo->findOneBy(array('id'=>$id));
        $service = $servicesRepo->findByCname($cname);
        if(empty($user)) throw new HttpException(404, 'User not found');
        if(empty($service)) throw new HttpException(404, 'Service not found');

        $user->addService($cname);
        $em = $this->getDoctrine()->getManager();
        $limitRepo = $em->getRepository("TelepayFinancialApiBundle:LimitCount");
        $limit = $limitRepo->findOneBy(array('cname' => $cname, 'user' => $user));
        if(!$limit){
            $limit = new LimitCount();
            $limit->setUser($user);
            $limit->setCname($cname);
            $limit->setSingle(0);
            $limit->setDay(0);
            $limit->setWeek(0);
            $limit->setMonth(0);
            $limit->setYear(0);
            $limit->setTotal(0);
            $em->persist($limit);
        }

        try{
            $em->flush();
        } catch(DBALException $e){
            if(preg_match('/SQLSTATE\[23000\]/',$e->getMessage()))
                throw new HttpException(409, "Duplicated resource");
            else
                throw new HttpException(500, "Unknown error occurred when save");
        }
    }

    private function _addMethod($id, $cname){
        $usersRepo = $this->getRepository();
        $methodsRepo = $this->get('net.telepay.method_provider');
        $user = $usersRepo->findOneBy(array('id'=>$id));
        $method = $methodsRepo->findByCname($cname);
        if(empty($user)) throw new HttpException(404, 'User not found');
        if(empty($method)) throw new HttpException(404, 'Method not found');

        $user->addMethod($cname);
        $em = $this->getDoctrine()->getManager();
        $limitRepo = $em->getRepository("TelepayFinancialApiBundle:LimitCount");
        $limit = $limitRepo->findOneBy(array('cname' => $cname, 'user' => $user));
        if(!$limit){
            $limit = new LimitCount();
            $limit->setUser($user);
            $limit->setCname($cname);
            $limit->setSingle(0);
            $limit->setDay(0);
            $limit->setWeek(0);
            $limit->setMonth(0);
            $limit->setYear(0);
            $limit->setTotal(0);
            $em->persist($limit);
        }

        try{
            $em->flush();
        } catch(DBALException $e){
            if(preg_match('/SQLSTATE\[23000\]/',$e->getMessage()))
                throw new HttpException(409, "Duplicated resource");
            else
                throw new HttpException(500, "Unknown error occurred when save");
        }
    }

    private function _deleteService($id, $cname){
        $usersRepo = $this->getRepository();
        $service = $this->get('net.telepay.service_provider')->findByCname($cname);
        $user = $usersRepo->findOneBy(array('id'=>$id));
        if(empty($user)) throw new HttpException(404, "User not found");
        if(empty($service)) throw new HttpException(404, 'Service not found');

        $user->removeService($cname);
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
    }


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
