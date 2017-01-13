<?php

namespace Telepay\FinancialApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class Login2faController extends RestApiController{
    public function loginAction(Request $request){
        $headers = array(
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
        );

        $clientId = $request->get('client_id');
        $clientSecret = $request->get('client_secret');
        $username = $request->get('username');
        $password = $request->get('password');
        $pin = $request->get('pin');
        $kyc = 0;
        if($request->request->has('kyc')) $kyc = $request->get('kyc');
        $fair = 0;
        if($request->request->has('fair')) $fair = $request->get('fair');

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array('email' => $username));
        $username=($user)?$user->getUsername():$username;
        $token = $this->call(
            "https://$_SERVER[HTTP_HOST]/oauth/v2/token",
            'POST',
            array(),
            array(
                'client_id'=> $clientId,
                'client_secret'=> $clientSecret,
                'username'=> $username,
                'password'=> $password,
                'grant_type'=> 'password'
            ),
            array('Accept'=>'application/json')
        );
        if(!isset($token->error)){
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('TelepayFinancialApiBundle:User')->findBy(array('username' => $username));

            if((count($user[0]->getKycValidations())==0) || (!$user[0]->getKycValidations()->getEmailValidated())){
                $token = array(
                    "error" => "not_validated_email",
                    "error_description" => "User without email validated"
                );
                return new Response(json_encode($token), 400, $headers);
            }

            if($user[0]->isEnabled()==false){
                $token = array(
                    "error" => "not_enabled",
                    "error_description" => "User not enabled to log in"
                );
                return new Response(json_encode($token), 400, $headers);
            }

            if($user[0]->getTwoFactorAuthentication() == 1) {
                $Google2FA = new Google2FA();
                $twoFactorCode = $user[0]->getTwoFactorCode();
                if (!$Google2FA->verify_key($twoFactorCode, $pin)) {
                    $token = array(
                        "error" => "invalid_grant",
                        "error_description" => "Invalid Google authenticator code"
                    );
                    return new Response(json_encode($token), 400, $headers);
                }
            }

            $groups = $em->getRepository('TelepayFinancialApiBundle:UserGroup')->findBy(array('user' => $user[0]->getId()));
            if($kyc == 0 && count($groups)<1){
                $token = array(
                    "error" => "no_company",
                    "error_description" => "You are not assigned to any company. Please contact your company administrator or write us to https://support.chip-chap.com/"
                );
                return new Response(json_encode($token), 400, $headers);
            }
            if($fair == 1 && !$user[0]->getActiveGroup()->getPremium()){
                $token = array(
                    "error" => "no_fairpay_user",
                    "error_description" => "Fairpay access denied"
                );
                return new Response(json_encode($token), 400, $headers);
            }
        }
        else{
            return new Response(json_encode($token), 400, $headers);
        }
        return new Response(json_encode($token), 200, $headers);
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
