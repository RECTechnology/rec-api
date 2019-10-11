<?php

namespace App\FinancialApiBundle\Controller\Management\Admin;

use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\BaseApiController;
use App\FinancialApiBundle\DependencyInjection\Transactions\Core\AbstractMethod;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\LimitCount;
use App\FinancialApiBundle\Entity\LimitDefinition;
use App\FinancialApiBundle\Entity\ResellerDealer;
use App\FinancialApiBundle\Entity\ServiceFee;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use App\FinancialApiBundle\Entity\User;
use FOS\OAuthServerBundle\Util\Random;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * Class UsersController
 * @package App\FinancialApiBundle\Controller\Manager
 */
class UsersController extends BaseApiController{
    function getRepositoryName(){
        return "FinancialApiBundle:User";
    }

    function getNewEntity(){
        return new User();
    }

    /**
     * @Rest\View
     */
    public function showAction($id){
        $admin_user = $this->get('security.token_storage')->getToken()->getUser();
        if(!$admin_user->hasRole('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');

        $user = $this->getRepository()->find($id);

        if(!$user) throw new HttpException(404,'User not found');

        $groups = array();

        foreach ($user->getGroups() as $group){
            $resumeView = array();
            $resumeView['id'] = $group->getId();
            $resumeView['name'] = $group->getName();
            $resumeView['tier'] = $group->getTier();
            $resumeView['roles'] = $user->getRolesCompany($group->getId());

            $groups[] = $resumeView;
        }

        $user->setGroupData($groups);

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => 1,
                'start' => 0,
                'end' => 1,
                'elements' => $user
            )
        );
    }

    /**
     * @Rest\View
     * permissions: ROLE_SUPER_ADMIN
     */
    public function updateAction(Request $request, $id){
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if(!$user->hasRole('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');

        $validParams = array(
            'email',
            'pin',
            'roles',
            'security_answer',
            'name',
            'public_phone',
            'password',
            'repassword',
            'twoFactorAuthentication',
            'profile_image',
            'document_front',
            'document_rear'
        );

        $params = $request->request->all();
        foreach($params as $paramName=>$value){
            if(!in_array($paramName, $validParams)){
                throw new HttpException(404, 'Param ' . $paramName . ' can not be updated');
            }
        }

        if($request->request->has('roles')){
            $roles = $request->request->get('roles');
            if(in_array('ROLE_SUPER_ADMIN', $roles)) throw new HttpException(403, 'Bad parameter role');
        }

        if($request->request->has('password')){
            if($request->request->has('repassword')){
                $password = $request->request->get('password');
                $repassword = $request->request->get('repassword');
                if($password != $repassword) throw new HttpException(400, "Password and repassword are differents.");
                $userManager = $this->container->get('access_key.security.user_provider');
                $user = $userManager->loadUserById($id);
                $user->setPlainPassword($request->request->get('password'));
                $userManager->updatePassword($user);
                $request->request->remove('password');
                $request->request->remove('repassword');
            }else{
                throw new HttpException(400,"Missing parameter 'repassword'");
            }
        }

        $em = $this->getDoctrine()->getManager();
        $kyc = $em->getRepository('FinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user
        ));

        if($request->request->has('document_front') ){
            $kyc->setDocumentFront($request->request->get('document_front'));
            $kyc->setDocumentFrontStatus('pending');
            $em->persist($kyc);
        }

        if($request->request->has('document_rear') ){
            $kyc->setDocumentRear($request->request->get('document_front'));
            $kyc->setDocumentRearStatus('pending');
            $em->persist($kyc);
        }

        $resp = parent::updateAction($request, $id);
        if($resp->getStatusCode() == 204){

            if($request->request->has('email') && $request->request->get('email')!=''){
                $kyc->setEmail($request->request->get('email'));
                $kyc->setEmailValidated(false);
                $em->persist($kyc);
            }
            if($request->request->has('name') && $request->request->get('name')!=''){
                $kyc->setName($request->request->get('name'));
                $em->persist($kyc);
            }
            $em->flush();
        }
        return $resp;
    }

    /**
     * @Rest\View
     * permissions: ROLE_SUPER_ADMIN
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function updateKYCAction(Request $request, $id){
        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if (!$user->hasRole('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');

        $validParams = array(
            'lastName',
            'dateBirth',
            'country',
            'neighborhood',
            'street_type',
            'street_number',
            'street_name',
            'nationality',
            'gender'
        );

        $params = $request->request->all();
        foreach ($params as $paramName => $value) {
            if (!in_array($paramName, $validParams)) {
                throw new HttpException(404, 'Param ' . $paramName . ' can not be updated');
            }
        }

        $params = $request->request->all();
        $repo = $this->getDoctrine()->getManager()->getRepository("FinancialApiBundle:KYC");
        $kycEntry = $repo->findOneBy(['user' => $id]);
        if(empty($kycEntry)) throw new HttpException(404, "Not found");

        try{
            foreach ($params as $name => $value) {
                if ($name != 'id') {
                    $setter = $this->attributeToSetter($name);
                    if (method_exists($kycEntry, $setter)) {
                        call_user_func(array($kycEntry, $setter), $value);
                    }
                    else{
                        throw new HttpException(400, "Bad request, parameter '$name' is not allowed");
                    }
                }
            }
        } catch(\LogicException $e){
            throw new HttpException(400, $e->getMessage());
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($kycEntry);
        try{
            $em->flush();
        } catch(DBALException $e){
            if(preg_match('/1062 Duplicate entry/i',$e->getMessage()))
                throw new HttpException(409, "Duplicated resource");
            else if(preg_match('/1048 Column/i',$e->getMessage()))
                throw new HttpException(400, "Bad parameters");
            throw new HttpException(500, "Unknown error occurred when save");
        }
        return $this->restV2(204,"ok", "Updated successfully");
    }

    /**
     * @Rest\View
     * permissions: ROLE_SUPER_ADMIN
     */
    public function updatePhoneAction(Request $request, $id){
        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if (!$user->hasRole('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');

        $validParams = array(
            'phone',
            'prefix'
        );

        $params = $request->request->all();
        foreach ($params as $paramName => $value) {
            if (!in_array($paramName, $validParams)) {
                throw new HttpException(404, 'Param ' . $paramName . ' can not be updated');
            }
        }

        $em = $this->getDoctrine()->getManager();
        $new = false;
        if($request->request->get('phone')!=''){
            $new = true;
            $phone = $request->request->get('phone');
            $duplicated_phone = $em->getRepository('FinancialApiBundle:User')->findOneBy(array(
                'phone' => $phone
            ));
            if($duplicated_phone){
                if($duplicated_phone->isEnabled()) {
                    throw new HttpException(404, 'Phone already registered');
                }
                else{
                    throw new HttpException(404, 'Phone used by an old user');
                }
            }

        }
        else{
            $phone = $user->getPhone();
        }
        if($request->request->get('prefix')!='') {
            $new = true;
            $prefix = $request->request->get('prefix');
        }
        else{
            $prefix = $user->getPrefix();
        }

        if(!$new){
            throw new HttpException(404, 'Not new phone');
        }

        if(!$this->checkPhone($phone, $prefix)){
            throw new HttpException(400, "Incorrect phone or prefix number");
        }

        //Table User
        $user = $em->getRepository('FinancialApiBundle:User')->findOneBy(array(
            'id' => $id
        ));
        $old_phone = $user->getPhone();
        $user->setPhone($phone);
        $user->setPrefix($prefix);
        $em->persist($user);

        //Table KYC
        $kyc = $em->getRepository('FinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user
        ));
        $code = substr(Random::generateToken(), 0, 6);
        $kyc->setPhoneValidated(false);
        $kyc->setValidationPhoneCode(json_encode(array("code" => $code, "tries" => 0)));
        $phone_info = array(
            "prefix" => $prefix,
            "number" => $phone
        );
        $kyc->setPhone(json_encode($phone_info));
        $this->sendSMS($prefix, $phone, "Rec Wallet Code " . $code);
        $em->persist($kyc);

        //Table Group
        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository('FinancialApiBundle:Group')->findOneBy(array(
            'kyc_manager' => $id
        ));
        if(strcmp($old_phone,$phone) == 0){
            $group->setPhone($phone);
            $group->setPrefix($prefix);
            $em->persist($group);
        }
        $em->flush();
        return $this->restV2(204, 'Success', 'Updated successfully');
    }

    private function checkPhone($phone, $prefix){
        if(strlen($prefix)<1){
            return false;
        }
        $first = substr($phone, 0, 1);

        //SP xxxxxxxxx
        if($prefix == '34' && ($first == '6' || $first == '7')){
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
     */
    public function deactivateAction($id){
        if(!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('FinancialApiBundle:User')->findOneBy(array(
                'id'=>$id
            )
        );
        if(empty($user)) throw new HttpException(404, 'User not found');
        $user->setLocked(true);
        $em->persist($user);
        $em->flush();
        return $this->restV2(204, 'Success', 'Deactivated successfully');
    }

    /**
     * @Rest\View
     */
    public function activateAction($id){
        if(!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('FinancialApiBundle:User')->findOneBy(array(
                'id'=>$id
            )
        );
        if(empty($user)) throw new HttpException(404, 'User not found');
        $user->setLocked(false);
        $em->persist($user);
        $em->flush();
        return $this->restV2(204, 'Success', 'Activated successfully');
    }


    /**
     * @Rest\View
     */
    public function deleteAction($id){
        if(!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');
        //TODO conditions to delete user
        //no transactions, not kyc manager in any company, if unique in company without transactions,
        //TODO a listener to control this shit
        throw new HttpException(403, 'Pending function');
        return parent::deleteAction($id);
    }

    /**
     * @Rest\View
     */
    public function deleteByNameAction($username){
        if(!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');
        throw new HttpException(403, 'Pending function');
        $repo = $this->getRepository();
        $user = $repo->findOneBy(array('username'=>$username));
        if(empty($user)) throw new HttpException(404, 'User not found');
        $idUser = $user->getId();
        return parent::deleteAction($idUser);
    }

    /**
     * @Rest\View
     */
    public function deleteFromGroupAction(Request $request, $user_id, $group_id){
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');

        $groupsRepository = $this->getDoctrine()->getRepository("FinancialApiBundle:Group");
        $group = $groupsRepository->find($group_id);
        if(!$group) throw new HttpException(404, "Group not found");

        $usersRepository = $this->getDoctrine()->getRepository("FinancialApiBundle:User");
        $user = $usersRepository->find($user_id);
        if(!$user) throw new HttpException(404, "User not found");

        $user_groups = $this->getDoctrine()->getRepository('FinancialApiBundle:UserGroup')->findBy(array(
            'user'  =>  $user->getId()
        ));

        if(count($user_groups)<2){
            throw new HttpException(404, "User can not be expel.");
        }

        $group_users = $this->getDoctrine()->getRepository('FinancialApiBundle:UserGroup')->findBy(array(
            'group'  =>  $group->getId()
        ));

        if(count($group_users)<2){
            throw new HttpException(404, "User can not be expel.");
        }

        $repo = $this->getDoctrine()->getRepository("FinancialApiBundle:UserGroup");
        $entity = $repo->findOneBy(array('user'=>$user_id, 'group'=>$group_id));
        if(empty($entity)) throw new HttpException(404, "Not found");
        $em = $this->getDoctrine()->getManager();
        $em->remove($entity);
        $em->flush();

        if($user->getActiveGroup()->getId() == $group_id){
            foreach($user_groups as $user_group){
                if($user_group->getGroup()->getId()!=$group_id){
                    $user->setActiveGroup($user_group->getGroup()->getId());
                }
            }
        }
        $em->persist($user);
        $em->flush();
        return $this->rest(204, "User removed successfully");
    }

    /**
     * @Rest\View
     * @param Request $request
     * @param $id
     * @param $action
     * @return Response
     */
    public function userActionsAction(Request $request,$id, $action){
        $em = $this->getDoctrine()->getmanager();
        $user = $em->getRepository($this->getRepositoryName())->find($id);
        if(!$user) throw new HttpException(404, 'User not found');
        //posible actions: resend_email, resend_sms,
        switch ($action){
            case 'resend_email':
                //Your own logic
                return $this->restV2(204, 'Success', 'Function not available');
                /*
                $email = $user->getEmail();
                $request->request->add(array(
                    'email' =>  $email
                ));
                $response = $this->forward('App\FinancialApiBundle\Controller\Management\User\AccountController::sentValidationEmailAction', array('request'=>$request));
                return $response;
                */
                break;
            case 'check_sms':
                $kyc = $em->getRepository('FinancialApiBundle:KYC')->findOneBy(array(
                    'user' => $user
                ));
                $phone_json = json_decode($kyc->getPhone());
                $prefix = $phone_json->prefix;
                $phone = $phone_json->number;
                $code_json = json_decode($kyc->getValidationPhoneCode());
                $code = $code_json->code;
                $tries = $code_json->tries;
                $response = array(
                    'prefix' => $prefix,
                    'phone' => $phone,
                    'code' => $code,
                    'tries' => $tries
                );
                return $this->restV2(200,"ok", "Request successful", $response);
                break;
            case 'resend_sms':
                $kyc = $em->getRepository('FinancialApiBundle:KYC')->findOneBy(array(
                    'user' => $user
                ));
                $phone_json = json_decode($kyc->getPhone());
                $prefix = $phone_json->prefix;
                $phone = $phone_json->number;
                $code_json = json_decode($kyc->getValidationPhoneCode());
                $code = $code_json->code;
                $this->sendSMS($prefix, $phone, "Rec Wallet Code " . $code);
                return $this->restV2(204, 'Success', 'SMS sent successfully');
                break;
            case 'reset_sms':
                //Your own logic
                $kyc = $em->getRepository('FinancialApiBundle:KYC')->findOneBy(array(
                    'user' => $user
                ));
                $phone_json = json_decode($kyc->getPhone());
                $prefix = $phone_json->prefix;
                $phone = $phone_json->number;
                if($prefix == "" || $phone == "") throw new HttpException(403, 'Invalid phone');
                $request->request->add(array(
                    'user'  =>  $user,
                    'prefix'    =>  $prefix,
                    'phone' =>  $phone
                ));
                $response = $this->forward('App\FinancialApiBundle\Controller\KycController::validatePhone', array('request' => $request));
                return $response;
                break;
            case 'validate_phone':
                $kyc = $em->getRepository('FinancialApiBundle:KYC')->findOneBy(array(
                    'user' => $user
                ));
                if($kyc){
                    $kyc->setPhoneValidated(true);
                    $em->persist($kyc);
                    $em->flush();
                    $user->setEnabled(true);
                    $em->persist($user);
                    $em->flush();
                }
                else{
                    throw new HttpException(400, 'User without kyc information');
                }
                return $this->restV2(201,"ok", "Request successful", $kyc);
                break;
            default:
                break;
        }

        return $this->restV2(204, 'Success', 'Updated successfully');
    }

    private function sendSMS($prefix, $number, $text){
        $user = $this->container->getParameter('labsmobile_user');
        $pass = $this->container->getParameter('labsmobile_pass');
        $text = str_replace(" ", "+", $text);

        $url = 'http://api.labsmobile.com/get/send.php?';
        $url .= 'username=' . $user . '&';
        $url .= 'password=' . $pass . '&';
        $url .= 'msisdn=' . $prefix . $number . '&';
        $url .= 'message=' . $text . '&';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $result = curl_exec($ch);
        curl_close($ch);
    }

}
