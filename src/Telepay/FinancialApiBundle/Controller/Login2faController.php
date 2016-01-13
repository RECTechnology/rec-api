<?php

namespace Telepay\FinancialApiBundle\Controller;

use FOS\OAuthServerBundle\Controller\TokenController as BaseController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Login2faController extends BaseController {

    public function tokenAction(Request $request)
    {
        $response = parent::tokenAction($request);
        // ... do custom stuff
        return $response;
    }

    public function loginAction(Request $request)
    {
        //http://stackoverflow.com/questions/17055721/call-a-method-after-user-login
        $clientId = $request->get('client_id');
        $clientSecret = $request->get('client_secret');
        $username = $request->get('email');
        $password = $request->get('password');
        $pin = $request->get('pin');

        /*
        return $this->call(
            'cp.api/app_dev.php/oauth/v2/token',
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
        */

        $token = array(
            'access_token' => 2,
            'expires_in' => 3,
            'token_type' => 3,
            'scope' => 3,
            'refresh_token' => 3
        );
        $headers = array(
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
        );
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
        $response->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if(!isset($response->status) && !isset($response->access_token)){
            $response = new \stdClass();
            $response->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $response->status="error";
            $response->message='Unexpected response (HttpCode='.$response->httpCode.')';
            $response->data=array();
        }
        curl_close ($ch);
        return $response;
    }
}
