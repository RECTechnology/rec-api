<?php

namespace App\FinancialApiBundle\Controller;

use App\FinancialApiBundle\Controller\Management\Admin\UsersController;
use App\FinancialApiBundle\DependencyInjection\App\Commons\AwardHandler;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UserChecker;
use App\FinancialApiBundle\Entity\AwardScoreRule;
use App\FinancialApiBundle\Entity\Config;
use App\FinancialApiBundle\Entity\ConfigurationSetting;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\UserSecurityConfig;
use App\FinancialApiBundle\Entity\UsersSmsLogs;
use FOS\OAuthServerBundle\Controller\TokenController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Entity\User;


class Login2faController extends RestApiController{


    public function loginAction(Request $request){

        $em = $this->getDoctrine()->getManager();

        $username = $request->get('username', '');

        if($request->get('grant_type') == "password"){
            /** @var UserChecker $user_checker */
            $user_checker = $this->container->get('net.app.commons.user_checker');
            $dni_val = $user_checker->validateUserIdentification($username);
            if(!$dni_val['result'])
                throw new HttpException(400, $dni_val['errors'][0]);
            $this->checkPlatformAndVersion($request, $username);
        }

        $headers = array(
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
        );

        $pin = $request->request->get('pin');
        $kyc = 0;
        if($request->request->has('kyc')) $kyc = $request->get('kyc');

        $client_info = explode("_", $request->get('client_id'));
        if(count($client_info)!=2){
            $token = array(
                "error" => "not_validated_client",
                "error_description" => "The client format is not valid"
            );
            return new Response(json_encode($token), 400, $headers);
        }
        $client_id = $client_info[0];
        $client = $em->getRepository('FinancialApiBundle:Client')->findOneBy(array('id' => $client_id));
        if(!$client){
            $token = array(
                "error" => "not_validated_client",
                "error_description" => "The client is not valid"
            );
            return new Response(json_encode($token), 400, $headers);
        }

        /** @var TokenController $tokenController */
        $tokenController = $this->get('fos_oauth_server.controller.token');
        $token = json_decode($tokenController->tokenAction($request)->getContent());

        if($request->get('grant_type') == "client_credentials"){
            return new Response(json_encode($token), 200, $headers);
        }

        /** @var User $user */
        $user = $em->getRepository('FinancialApiBundle:User')
            ->findOneBy(['usernameCanonical' => strtolower($username)]);

        /** @var UserSecurityConfig $user_security_config */
        $unlock_user_config = $em->getRepository('FinancialApiBundle:UserSecurityConfig')
            ->findOneBy(['type' => UserSecurityConfig::USER_SECURITY_CONFIG_TYPE_SMS_UNLOCK_USER]);

        $password_failures_config = $em->getRepository('FinancialApiBundle:UserSecurityConfig')
            ->findOneBy(['type' => UserSecurityConfig::USER_SECURITY_CONFIG_TYPE_PASSWORD_FAILURES]);

        $max_password_fail_attempts = $password_failures_config->getMaxAttempts();

        if($user && !$user->isAccountNonLocked()){
            if($user->getPasswordFailures() >= $max_password_fail_attempts){

                $lastSmsSent = $em->getRepository(UsersSmsLogs::class)->findBy(
                    array("user_id" => $user->getId(), "type" => "sms_unlock_user"),
                    array('created'=> 'DESC'),1,0);

                $time_range = $unlock_user_config->getTimeRange();
                $now = new \DateTime();

                if(!isset($lastSmsSent[0]) || $now->getTimestamp() > $lastSmsSent[0]->getCreated()->getTimeStamp() + $time_range){
                    $this->_sendPasswordMaxFailuresSms($em, $user);
                }

                $token = array(
                    "error" => "user_locked",
                    "error_description" => "User locked to protect user security"
                );
            }else{
                $token = array(
                    "error" => "user_locked",
                    "error_description" => "User locked by admin"
                );
            }
            return new Response(json_encode($token), 403, $headers);
        }

        if(!isset($token->error)){

            $admin_client = $this->container->getParameter('admin_client_id');

            if(!$user->getKycValidations() || (!$user->getKycValidations()->getPhoneValidated())){
                $token = array(
                    "error" => "not_validated_phone",
                    "error_description" => "User without phone validated"
                );
                return new Response(json_encode($token), 400, $headers);
            }

            if(!$user->isEnabled()){
                $token = array(
                    "error" => "not_enabled",
                    "error_description" => "User not enabled to log in"
                );
                return new Response(json_encode($token), 400, $headers);
            }

            if($admin_client == $client->getId()){
                //Trying to access to admin panel
                $userGroups = $user->getGroups();
                $admin_group_id = $this->container->getParameter('id_group_root');
                foreach ($userGroups as $userGroup){
                    if($userGroup->getId() == $admin_group_id){
                        $user->setActiveGroup($userGroup);
                        $user->setRoles($user->getRoles());
                        $em->flush();
                    }
                }

                if(!$user->hasRole('ROLE_SUPER_ADMIN')) {
                    $token = array(
                        "error" => "not_permisssions",
                        "error_description" => "You do not have the necessary permissions"
                    );
                    return new Response(json_encode($token), 400, $headers);
                }

                if($user->getTwoFactorAuthentication() == 1) {
                    $authenticator = new Google2FA();
                    $twoFactorCode = $user->getTwoFactorCode();
                    if (!$authenticator->verify_key($twoFactorCode, $pin)) {
                        $token = array(
                            "error" => "invalid_2fa",
                            "error_description" => "Invalid 2fa authenticator code"
                        );
                        return new Response(json_encode($token), 400, $headers);
                    }
                }
                else{
                    $token = array(
                        "error" => "inactive_2fa",
                        "error_description" => "The 2fa authenticator must be active"
                    );
                    return new Response(json_encode($token), 400, $headers);
                }
            }

            $groups = $em->getRepository('FinancialApiBundle:UserGroup')->findBy(array('user' => $user->getId()));
            if($kyc == 0 && count($groups)<1){
                $token = array(
                    "error" => "no_company",
                    "error_description" => 'You are not assigned to any company. Please contact with the system administrators.'
                );
                return new Response(json_encode($token), 400, $headers);
            }
        }
        else{
            if($user && $token->error_description == 'Invalid username and password combination'){
                $user->setPasswordFailures($user->getPasswordFailures() + 1);
                $em->persist($user);
                $em->flush();

                if($user->getPasswordFailures() >= $max_password_fail_attempts){
                    $user->lockUser();

                    $this->_sendPasswordMaxFailuresSms($em, $user);

                    $token = array(
                        "error" => "user_locked",
                        "error_description" => "User locked to protect user security"
                    );
                    return new Response(json_encode($token), 403, $headers);
                }
            }
            return new Response(json_encode($token), 400, $headers);
        }
        if($user->getPasswordFailures() > 0){
            $user->setPasswordFailures(0);
            $em->persist($user);
            $em->flush();
        }

        if($request->request->get('platform') === 'rezero-b2b-web'){
            $this->addLoginScore($user);
        }
        return new Response(json_encode($token), 200, $headers);
    }

    public function publicAction(Request $request){
        $headers = array(
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
        );

        $username = $request->get('username');
        $password = $request->get('password');

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('FinancialApiBundle:User')->findOneBy(array('email' => $username));
        $username=($user)?$user->getUsername():$username;


        /** @var TokenController $tokenController */
        $tokenController = $this->get('fos_oauth_server.controller.token');
        $token = json_decode($tokenController->tokenAction($request)->getContent());
        if(!isset($token->error)){
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('FinancialApiBundle:User')
                ->findOneBy(['username' => $username]);

            if((count($user->getKycValidations())==0) || (!$user->getKycValidations()->getEmailValidated())){
                $token = array(
                    "error" => "not_validated_email",
                    "error_description" => "User without email validated"
                );
                return new Response(json_encode($token), 400, $headers);
            }

            if($user->isEnabled()==false){
                $token = array(
                    "error" => "not_enabled",
                    "error_description" => "User not enabled to log in"
                );
                return new Response(json_encode($token), 400, $headers);
            }
        }
        else{
            return new Response(json_encode($token), 400, $headers);
        }
        $data = array(
            'username' => $username,
            'access_secret' => substr($user->getAccessSecret(), 0, 10),
            'access_key' => substr($user->getId() .  "A" . rand(10,1000) . "C"  . rand(10,1000) . "E" . rand(10,1000), 0, 10)
        );
        return new Response(json_encode($data), 200, $headers);
    }

    public function call($func, $method, $urlParams = array(), $params = array(), $headers = array()){
        $ch = curl_init($func.'?'.http_build_query($urlParams));

        $curlHeaders = array();
        foreach($headers as $key => $value){
            $curlHeaders []= ucfirst($key).': '.$value;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $method = strtoupper($method);
        switch($method){
            case "GET":
                break;
            case "POST":
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                break;
            case "DELETE":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            case "PUT":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                break;
        }
        $response = json_decode(curl_exec($ch));
        curl_close ($ch);
        return $response;
    }

    private function _sendPasswordMaxFailuresSms($em, User $user){
        $sms_text = $em->getRepository('FinancialApiBundle:SmsTemplates')
            ->findOneBy(['type' => 'password_max_failures'])->getBody();
        $code = strval(random_int(100000, 999999));
        $user->setLastSmscode($code);
        $em->persist($user);
        $sms_text = str_replace("%SMS_CODE%", $code, $sms_text);
        UsersController::sendSMSv4($user->getPrefix(), $user->getPhone(), $sms_text, $this->container);

        $user_sms_log = new UsersSmsLogs();
        $user_sms_log->setUserId($user->getId());
        $user_sms_log->setType('sms_unlock_user');
        $user_sms_log->setSecurityCode($code);
        $em->persist($user_sms_log);
        $em->flush();
    }

    private function checkPlatformAndVersion(Request $request, String  $username){

        $em = $this->getDoctrine()->getManager();

        if(!$request->request->has('platform') ) {
            throw new HttpException(404, 'Platform es requerido');
        }

        $min_versions = $em->getRepository(Config::class)->findAll();

        if(!$min_versions) throw new HttpException(404, 'Min version no encontrado, por favor contacta con support');

        switch ($request->request->get('platform')){
            case 'android':
                $required_version = $min_versions[0]->getMinVersionAndroid();
                break;
            case 'ios':
                $required_version = $min_versions[0]->getMinVersionIos();
                break;
            case 'rec-admin':
                $required_version = 0;
                //Quick fix until version is sent from panel
                if(!$request->request->has('version')) $request->request->add(['version' => INF]);
                break;
            case 'rec-pos':
                $required_version = 0;
                //Quick fix until version is sent from pos
                if(!$request->request->has('version')) $request->request->add(['version' => INF]);
                break;
            case 'rezero-b2b-web':
                $required_version = 0;
                //Quick fix until version is sent from B2B
                if(!$request->request->has('version')) $request->request->add(['version' => INF]);
                /** @var User $user */
                $user = $em->getRepository(User::class)->findOneBy(['usernameCanonical' => strtolower($username)]);
                $account = $user->getActiveGroup();
                if(!isset($account))
                    throw new HttpException(403, 'User has no active account');
                if($account->getRezeroB2bAccess() == Group::ACCESS_STATE_PENDING)
                    throw new HttpException(403, 'User pending validation');
                if($account->getRezeroB2bAccess() == Group::ACCESS_STATE_NOT_GRANTED)
                    throw new HttpException(403, 'User not granted on this platform');
                break;
            default:
                throw new HttpException(403, 'Platform '.$request->request->get('platform').' inválido');
        }

        if($request->request->has('version')) {  // panel pass
            if ($request->request->get('version') < $required_version) {
                //TODO: Volver a poner mensaje "Must update"
                throw new HttpException(404, 'App. obsoleta, desinstala y vuelve a descargarla desde GooglePlay/AppStore');
            }
        }else{
            throw new HttpException(404, 'Version param es requerido');
        }
    }

    private function addLoginScore(User $user){
        $em = $this->getDoctrine()->getManager();
        /** @var AwardScoreRule $awardRule */
        $awardRule = $em->getRepository(AwardScoreRule::class)->findOneBy(array(
            'action' => 'login',
            'scope' => null,
            'category' => null
        ));
        if($awardRule){
            /** @var AwardHandler $awardHandler */
            $awardHandler = $this->get('net.app.commons.award_handler');
            $awardHandler->createAccountAwardItem($user->getActiveGroup(), $awardRule, null, null);
        }

    }
}
