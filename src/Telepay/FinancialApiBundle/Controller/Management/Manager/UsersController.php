<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Manager;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\User;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\UserGroup;
use Telepay\FinancialApiBundle\Entity\KYC;

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

            $last_login = $this->getDoctrine()->getRepository('TelepayFinancialApiBundle:AccessToken')->findOneBy(
                array(
                    'user'  =>  $elem->getId()
                ),
                array(
                    'expiresAt'    =>  'DESC'
                )
            );
            if($last_login){
                $last = new \DateTime();
                $last->setTimestamp($last_login->getExpiresAt() - 3600);
                $elem->setLastLogin($last);
            }

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
     * ROLE_SUPER_ADMIN: can create user in all groups (with all roles)
     * ROLE_ADMIN: can create the user in all groups where is ROLE_ADMIN (with roles <= itself)
     * ROLE_WORKER: can't create users
     * ROLE_READONLY: can't create users
     */
    public function createAction(Request $request){
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $usersRepo = $em->getRepository("TelepayFinancialApiBundle:User");
        $groupsRepo = $em->getRepository("TelepayFinancialApiBundle:Group");

        $role_array = array();
        if ($this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            if($request->request->has('group_id')) {
                $groupId = $request->request->get('group_id');
                $request->request->remove('group_id');
            }
            else{
                $groupId = $admin->getActiveGroup()->getId();
            }
        }
        elseif($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')){
            $request->request->remove('group_id');
            $groupId = $admin->getActiveGroup()->getId();
        }
        else{
            throw new HttpException(403, 'You don\'t have the necessary permissions to add a user in this group');
        }

        $group = $groupsRepo->find($groupId);
        if (!$group) throw new HttpException(404, 'Group not found');
        $request->request->add(array(
            'active_group' => $group
        ));

        if (!$request->request->has('role')) throw new HttpException(404, 'Parameter Role not found');
        if($request->request->get('role') == 'ROLE_SUPERADMIN'){
            throw new HttpException(404, 'Parameter role not valid');
        }
        $role_array[] = $request->request->get('role');
        $request->request->remove('role');

        if(!$request->request->has('username') || $request->request->get('username') == '') throw new HttpException(400, "Missing parameter 'username'");
        if(!$request->request->has('email') || $request->request->get('email') == ''){
            throw new HttpException(400, "Missing parameter 'email'");
        }else{
            if(!filter_var($request->request->get('email'), FILTER_VALIDATE_EMAIL)) throw new HttpException(404, 'Email provided is not valid');
        }
        if(!$request->request->has('name') || $request->request->get('name') == '') throw new HttpException(400, "Missing parameter 'name'");
        if(!$request->request->has('password')) throw new HttpException(400, "Missing parameter 'password'");
        if(!$request->request->has('repassword')) throw new HttpException(400, "Missing parameter 'repassword'");
        $password = $request->get('password');
        $repassword = $request->get('repassword');
        if($password == '') throw new HttpException(400, "Password parameter is empty.");
        if($password != $repassword) throw new HttpException(400, "Password and repassword are differents.");
        $request->request->remove('password');
        $request->request->remove('repassword');
        $request->request->add(array('plain_password'=>$password));
        $request->request->add(array('enabled'=>1));
        $request->request->add(array('base64_image'=>''));
        $request->request->add(array('gcm_group_key'=>''));

        $resp= parent::createAction($request);
        if($resp->getStatusCode() == 201){
            $user_id = $resp->getContent();
            $user_id = json_decode($user_id);
            $user_id = $user_id->data;
            $user_id = $user_id->id;
            $user = $usersRepo->findOneBy(array('id'=>$user_id));

            $tokenGenerator = $this->container->get('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());
            $em->persist($user);
            $em->flush();

            $userGroup = new UserGroup();
            $userGroup->setUser($user);
            $userGroup->setGroup($group);
            $userGroup->setRoles($role_array);
            $em = $this->getDoctrine()->getManager();
            $em->persist($userGroup);
            $em->flush();

            $user_kyc = new KYC();
            $user_kyc->setEmail($user->getEmail());
            $user_kyc->setUser($user);
            $em->persist($user_kyc);
            $em->flush();

            $url = $this->container->getParameter('base_panel_url');
            $url = $url.'/user/validation/'.$user->getConfirmationToken();
            $this->_sendEmail('Chip-Chap validation e-mail', $url, $user->getEmail(), 'register');
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
            if($request->request->has('email') && $request->request->get('email')!=''){
                $em = $this->getDoctrine()->getManager();
                $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
                    'user' => $user
                ));
                $kyc->setEmail($request->request->get('email'));
                $kyc->setEmailValidated(false);
                $em->persist($kyc);
            }
        }
        return $resp;
    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        if(!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');
        //TODO conditions to delete user
        //no transactions, not kyc manager in any company, if unique in company without transactions,
        //TODO a listener to control this shit
        return parent::deleteAction($id);
    }

    /**
     * @Rest\View
     */
    public function deleteByNameAction($username){
        if(!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');
        $repo = $this->getRepository();
        $user = $repo->findOneBy(array('username'=>$username));
        if(empty($user)) throw new HttpException(404, 'User not found');
        $idUser = $user->getId();
        return parent::deleteAction($idUser);
    }

    public function _setRole(Request $request, $id){
        $roleName = $request->get('role');

        if(empty($roleName))
            throw new HttpException(400, "Missing parameter 'role'");
        if($roleName != 'ROLE_SUPER_ADMIN' and $roleName != 'ROLE_ADMIN' and $roleName != 'ROLE_USER' and $roleName != 'ROLE_WORKER' and $roleName != 'ROLE_READONLY'){
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

    private function _sendEmail($subject, $body, $to, $action){
        $from = 'no-reply@chip-chap.com';
        $mailer = 'mailer';
        if($action == 'register'){
            $template = 'TelepayFinancialApiBundle:Email:registerconfirm.html.twig';
        }else{
            $template = 'TelepayFinancialApiBundle:Email:registerconfirm.html.twig';
        }
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
                            'message'        =>  $body
                        )
                    )
            )
            ->setContentType('text/html');

        $this->container->get($mailer)->send($message);
    }

}
