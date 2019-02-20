<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\AbstractMethod;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\LimitCount;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\ResellerDealer;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\User;

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
    public function showAction($id){

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
        $user = $this->get('security.context')->getToken()->getUser();
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
            '$twoFactorAuthentication'
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

        $resp = parent::updateAction($request, $id);
        if($resp->getStatusCode() == 204){
            $em = $this->getDoctrine()->getManager();
            $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
                'user' => $user
            ));
            if($request->request->has('email') && $request->request->get('email')!=''){
                $kyc->setEmail($request->request->get('email'));
                $kyc->setEmailValidated(false);
                $em->persist($kyc);
            }
            if($request->request->has('name') && $request->request->get('name')!=''){
                $kyc->setName($request->request->get('name'));
                $em->persist($kyc);
            }
        }
        $em->flush();
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
        throw new HttpException(403, 'Pending function');
        return parent::deleteAction($id);
    }

    /**
     * @Rest\View
     */
    public function deleteByNameAction($username){
        if(!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');
        throw new HttpException(403, 'Pending function');
        $repo = $this->getRepository();
        $user = $repo->findOneBy(array('username'=>$username));
        if(empty($user)) throw new HttpException(404, 'User not found');
        $idUser = $user->getId();
        return parent::deleteAction($idUser);
    }

    /**
     * @Rest\View
     * @param Request $request
     * @param $id
     * @param $action
     * @return \Symfony\Component\HttpFoundation\Response
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
                $response = $this->forward('Telepay\FinancialApiBundle\Controller\Management\User\AccountController::sentValidationEmailAction', array('request'=>$request));
                return $response;
                */
                break;
            case 'check_sms':
                $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
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
                $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
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
                $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
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
                $response = $this->forward('Telepay\FinancialApiBundle\Controller\KycController::validatePhone', array('request' => $request));
                return $response;
                break;
            case 'validate_phone':
                $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
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
