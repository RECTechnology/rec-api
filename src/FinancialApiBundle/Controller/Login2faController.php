<?php

namespace App\FinancialApiBundle\Controller;

use FOS\OAuthServerBundle\Controller\TokenController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Entity\User;


class Login2faController extends RestApiController{


    public function loginAction(Request $request){
        if($request->request->has('version')) {  // panel pass
            if(!$request->request->has('platform') ) { // older apps
                $required_version = 61; // todo get required_version from database
                if($request->request->get('version') < $required_version) {
                    throw new HttpException(404, 'Must update');
                }
            } else { // newer apps
                $required_version = INF;
                if($request->request->get('platform') == 'android'){
                    $required_version = 61;
                } elseif ($request->request->get('platform') == 'ios'){
                    $required_version = 61;
                }
                if ($request->request->get('version') < $required_version) {
                    throw new HttpException(404, 'Must update');
                }
            }
        }

        $headers = array(
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
        );

        $username = strtoupper($request->get('username'));
        $username = preg_replace("/[^0-9A-Z]/", "", $username);
        $pin = $request->request->get('pin');
        $kyc = 0;
        if($request->request->has('kyc')) $kyc = $request->get('kyc');

        /** @var TokenController $tokenController */
        $tokenController = $this->get('fos_oauth_server.controller.token');
        $token = json_decode($tokenController->tokenAction($request)->getContent());


        if(!isset($token->error)){
            $em = $this->getDoctrine()->getManager();

            $admin_client = $this->container->getParameter('admin_client_id');
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

            if($request->get('grant_type') == "client_credentials"){
                return new Response(json_encode($token), 200, $headers);
            }

            /** @var User $user */
            $user = $em->getRepository('FinancialApiBundle:User')
                ->findOneBy(['username' => $username]);

            if(!$user->getKycValidations() || (!$user->getKycValidations()->getPhoneValidated())){
                $token = array(
                    "error" => "not_validated_phone",
                    "error_description" => "User without phone validated"
                );
                return new Response(json_encode($token), 400, $headers);
            }

            if(!$user->isEnabled() or !$user->isAccountNonLocked()){
                $token = array(
                    "error" => "not_enabled",
                    "error_description" => "User not enabled to log in"
                );
                return new Response(json_encode($token), 400, $headers);
            }

            if($admin_client == $client->getId()){
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
            return new Response(json_encode($token), 400, $headers);
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
}
