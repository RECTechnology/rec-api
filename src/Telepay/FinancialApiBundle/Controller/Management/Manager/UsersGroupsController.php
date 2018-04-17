<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Manager;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Entity\UserGroup;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use Telepay\FinancialApiBundle\Entity\CashInTokens;
use Telepay\FinancialApiBundle\Financial\Currency;

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

        //check if is superadmin
        if(!$admin->hasRole('ROLE_SUPER_ADMIN')){
            //check if this user is admin of this group
            if(!$admin->hasGroup($company->getName()) || !$adminRoles->hasRole('ROLE_ADMIN'))
                throw new HttpException(409, 'You don\'t have the necesary permissions');
        }

        $usersRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:User");

        //check parameters
        if(!$request->request->has('user_dni')){
            throw new HttpException(404, 'Param user_dni not found');
        }else{
            $user = $usersRepository->findOneBy(array(
                'username' =>  strtoupper($request->request->get('user_dni'))
            ));
            if(!$user) throw new HttpException(404, "User not found");
        }

        if(!$request->request->has('role')) throw new HttpException(404, 'Param role not found');

        $role = $request->request->get('role');
        if($role != ''){
            $role_array = array($role);
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

        //send SMS
        //$url = '';
        //$this->_sendEmail('You have been added to this company', $user->getEmail(), $url, $company->getName());

        return $this->restV2(201, "ok", "User added successfully");
    }

    /**
     * @Rest\View
     * description: remove user from company
     * permissions: ROLE_ADMIN (active company), ROLE_SUPER_ADMIN(all)
     */
    public function deleteAction(Request $request, $user_id, $group_id){

        $admin = $this->get('security.context')->getToken()->getUser();

        $adminRoles = $this->getDoctrine()->getRepository('TelepayFinancialApiBundle:UserGroup')->findOneBy(array(
            'user'  =>  $admin->getId(),
            'group' =>  $admin->getActiveGroup()->getId()
        ));

        if(!$adminRoles->hasRole('ROLE_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');

        $groupsRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:Group");
        $group = $groupsRepository->find($group_id);
        if(!$group) throw new HttpException(404, "Group not found");

        $usersRepository = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:User");
        $user = $usersRepository->find($user_id);
        if(!$user) throw new HttpException(404, "User not found");

        if(!$admin->hasGroup($group->getName()) && !$admin->hasRole('ROLE_SUPER_ADMIN')) throw new HttpException(409, 'You don\'t have the necesary permissions');

        $repo = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:UserGroup");
        $entity = $repo->findOneBy(array('user'=>$user_id, 'group'=>$group_id));
        if(empty($entity)) throw new HttpException(404, "Not found");
        $em = $this->getDoctrine()->getManager();
        $em->remove($entity);
        $em->flush();

        return $this->rest(204, "User removed successfully");

    }


    /**
     * @Rest\View
     * description: create new company
     * permissions: all
     */
    public function createCompanyAction(Request $request){
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $allowed_types = array('PRIVATE', 'COMPANY');
        if($request->request->has('type') && $request->request->get('type')!='') {
            $type = $request->request->get('type');
            if(!in_array($type, $allowed_types)) {
                throw new HttpException(400, "Invalid type");
            }
        }
        else{
            $type = $allowed_types[0];
        }

        $list_subtypes = array(
            'PRIVATE' => array('NORMAL', 'BMINCOME'),
            'COMPANY' => array('RETAILER', 'WHOLESALE')
        );
        $allowed_subtypes = $list_subtypes[$type];
        if($request->request->has('subtype') && $request->request->get('subtype')!='') {
            $subtype = $request->request->get('subtype');
            if(!in_array($subtype, $allowed_subtypes)) {
                throw new HttpException(400, "Invalid subtype");
            }
        }
        else{
            $subtype = $allowed_subtypes[0];
        }

        if($admin->getActiveGroup()->getSubType() == 'BMINCOME' && $type == 'PRIVATE' && $subtype != 'BMINCOME'){
            throw new HttpException(400, "User can create a private account");
        }

        if($request->request->has('account_name') && $request->request->get('account_name')!='') {
            $company_name = $request->request->get('account_name');
        }
        else{
            throw new HttpException(400, "Account name required");
        }

        $allGroups = $this->getDoctrine()->getRepository('TelepayFinancialApiBundle:UserGroup')->findBy(array(
            'user'  =>  $admin->getId()
        ));
        foreach($allGroups as $group){
            if($group->getName() == $company_name){
                throw new HttpException(400, "Account name duplicated");
            }
        }

        if($request->request->has('company_cif') && $request->request->get('company_cif')!='') {
            $company_cif = $request->request->get('company_cif');
        }
        else{
            if($type == 'COMPANY'){ throw new HttpException(400, "Company cif required"); }
            $company_cif = $admin->getDNI();
        }

        $account = $em->getRepository('TelepayFinancialApiBundle:Group')->findOneBy(array(
            'cif'  =>  $company_cif
        ));
        if($account){
            throw new HttpException(400, "CIF already registered");
        }

        $methodsList = array('rec-out', 'rec-in');

        //create company
        $company = new Group();
        if($request->request->has('company_email') && $request->request->get('company_email')!='') {
            $company->setEmail($request->request->get('company_email'));
        }
        else{
            $company->setEmail($admin->getEmail());
        }

        if($type == 'COMPANY'){
            if($request->request->has('company_phone') && $request->request->get('company_phone')!='') {
                $phone_com = preg_replace("/[^0-9]/", "", $request->request->get('company_phone'));
                $company->setPhone($phone_com);
            }
            if($request->request->has('company_prefix') && $request->request->get('company_prefix')!='') {
                $prefix_com = preg_replace("/[^0-9]/", "", $request->request->get('company_prefix'));
                $company->setPrefix($prefix_com);
            }
            if(!$this->checkPhone($phone_com, $prefix_com)){
                throw new HttpException(400, "Incorrect phone or prefix company number");
            }
        }

        $company->setName($company_name);
        $company->setCif($company_cif);
        $company->setType($type);
        $company->setSubtype($subtype);
        $company->setActive(true);
        $company->setRoles(array('ROLE_COMPANY'));
        $company->setRecAddress('temp');
        $company->setMethodsList($methodsList);
        $company->setKycManager($admin);
        $em->persist($company);

        //create wallets for this company
        $currencies = Currency::$ALL;
        foreach($currencies as $currency){
            $userWallet = new UserWallet();
            $userWallet->setBalance(0);
            $userWallet->setAvailable(0);
            $userWallet->setCurrency(strtoupper($currency));
            $userWallet->setGroup($company);
            $em->persist($userWallet);
        }

        //Add user to group with admin role
        $userGroup = new UserGroup();
        $userGroup->setUser($admin);
        $userGroup->setGroup($company);
        $userGroup->setRoles(array('ROLE_ADMIN'));

        $em->persist($userGroup);
        $em->flush();

        foreach($methodsList as $method){
            $method_ex = explode('-', $method);
            $meth = $method_ex[0];
            $meth_type = $method_ex[1];

            $newFee = new ServiceFee();
            $newFee->setGroup($company);
            $newFee->setFixed(0);
            $newFee->setVariable(0);
            $newFee->setServiceName($method);
            $newFee->setCurrency(strtoupper($meth));
            $em->persist($newFee);
        }

        //create new fixed address for rec and return
        $recAddress = new CashInTokens();
        $recAddress->setCurrency(Currency::$REC);
        $recAddress->setCompany($company);
        $recAddress->setLabel('REC account');
        $recAddress->setMethod('rec-in');
        $recAddress->setExpiresIn(-1);
        $recAddress->setStatus(CashInTokens::$STATUS_ACTIVE);
        $methodDriver = $this->get('net.telepay.in.rec.v1');
        $paymentInfo = $methodDriver->getPayInInfo(0);
        $token = $paymentInfo['address'];
        $recAddress->setToken($token);
        $em->persist($recAddress);

        $company->setRecAddress($token);
        $em->persist($company);

        $response['company'] = $company;
        $em->flush();
        return $this->restV2(201,"ok", "Request successful", $response);
    }

    private function checkPhone($phone, $prefix){
        if(strlen($prefix)<1){
            return false;
        }

        //SP xxxxxxxxx
        if($prefix == '34'){
            return strlen($phone)==9;
        }
        //PL xxxxxxxxx
        elseif($prefix == '48'){
            return strlen($phone)==9;
        }
        //GR xxxxxxxxx
        elseif($prefix == '30'){
            return strlen($phone)==10;
        }
        //GB 07xxx xxxxxx
        elseif($prefix == '44'){
            return strlen($phone)==11;
        }
        elseif(strlen($phone)>7){
            return true;
        }
        return false;
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
        $entity = $usersRolesRepository->findOneBy(array(
            'user'   =>  $user_id,
            'group'  =>  $group_id
        ));
        if(empty($entity)) throw new HttpException(404, "User Roles not found");

        if(!$request->request->has('role')) throw new HttpException(404, 'Param role not found');
        $role[] = $request->request->get('role');

        if(!$this->get('security.context')->isGranted('ROLE_ADMIN')) throw new HttpException(403, 'You don\' have the necessary permissions');

        //check if is superadmin but readonly
        if(!$admin->hasRole('ROLE_ADMIN')) throw new HttpException(403, 'You are READ ONLY. you don\'t have the necessary permissions');

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

        //TODO this function is used now??? check and delete
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