<?php

namespace Telepay\FinancialApiBundle\Controller;

use Telepay\FinancialApiBundle\Controller\RestApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\OAuthServerBundle\Util\Random;

use Telepay\FinancialApiBundle\Entity\KYC;
use Telepay\FinancialApiBundle\Entity\TierValidations;
use Telepay\FinancialApiBundle\Entity\User;

class KycController extends RestApiController{

    public function registerKYCAction(Request $request){
        if(!$request->request->has('email') || $request->get('email')==""){
            throw new HttpException(400, "Missing parameter 'email'");
        }
        else{
            $email = $request->request->get('email');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new HttpException(400, "Invalid email");
            }
        }
        if(!$request->request->has('password') || $request->get('password')==""){
            throw new HttpException(400, "Missing parameter 'password'");
        }
        if(!$request->request->has('repassword')){
            throw new HttpException(400, "Missing parameter 'repassword'");
        }
        else{
            $password = $request->get('password');
            $repassword = $request->get('repassword');
            if($password!=$repassword) throw new HttpException(400, "Password and repassword are differents.");
            $request->request->add(array('plain_password'=>$password));
            $request->request->remove('password');
            $request->request->remove('repassword');
        }
        $request->request->add(array('username'=>$email));
        $request->request->add(array('name'=>''));
        $request->request->add(array('phone'=>''));
        $request->request->add(array('prefix'=>''));
        $request->request->add(array('email'=>$email));
        $request->request->add(array('enabled'=>1));
        $request->request->add(array('base64_image'=>''));
        $request->request->add(array('default_currency'=>'EUR'));
        $request->request->add(array('gcm_group_key'=>''));
        $request->request->add(array('services_list'=>array('sample')));
        $request->request->add(array('methods_list'=>array('sample')));
        $resp= parent::createAction($request);

        if($resp->getStatusCode() == 201) {
            $em = $this->getDoctrine()->getManager();

            $usersRepo = $em->getRepository("TelepayFinancialApiBundle:User");
            $data = $resp->getContent();
            $data = json_decode($data);
            $data = $data->data;
            $user_id = $data->id;

            $user = $usersRepo->findOneBy(array('id'=>$user_id));

            $user_kyc = new KYC();
            $user_kyc->setEmail($email);
            $user_kyc->setUser($user);
            $em->persist($user_kyc);
            $user->addRole('ROLE_KYC');
            $em->persist($user);
            $em->flush();

            $url = $this->container->getParameter('web_app_url');
            $tokenGenerator = $this->container->get('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());
            $em->persist($user);
            $em->flush();
            $url = $url.'?user_token='.$user->getConfirmationToken();
            $this->_sendEmail('Chip-Chap validation e-mail', $url, $user->getEmail(), 'register_kyc');


            $em->persist($user);
            $em->flush();

            $response = array(
                'id'        =>  $user_id,
                'username'  =>  $email,
                'password'   =>  $password
            );

            return $this->restV2(201,"ok", "Request successful", $response);
        }else{
            return $resp;
        }
    }

    public function kycInfo(Request $request){
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user
        ));
        if(!$kyc){
            throw new HttpException(400, 'User without kyc information');
        }
        return $this->restV2(201,"ok", "Request successful", $kyc);
    }

    public function validateEmail(Request $request){
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $url = $this->container->getParameter('web_app_url');
        $tokenGenerator = $this->container->get('fos_user.util.token_generator');
        $user->setConfirmationToken($tokenGenerator->generateToken());
        $em->persist($user);
        $em->flush();
        $url = $url.'?user_token='.$user->getConfirmationToken();
        $this->_sendEmail('Chip-Chap validation e-mail', $url, $user->getEmail(), 'register_kyc');
    }

    public function validateEmailCode(Request $request){

        $em = $this->getDoctrine()->getManager();

        if(!$request->request->has('confirmation_token')) throw new HttpException(404, 'Param confirmation_token not found');

        $user = $em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array(
            'confirmationToken' => $request->request->get('confirmation_token')
        ));

        if(!$user) throw new HttpException(400, 'User not found');

        $tierValidation = $em->getRepository('TelepayFinancialApiBundle:TierValidations')->findOneBy(array(
            'user' => $user
        ));

        if(!$tierValidation){
            $tier = new TierValidations();
            $tier->setUser($user);
            $tier->setEmail(true);

            $em->persist($tier);
            $em->flush();
        }else{
            throw new HttpException(409, 'Validation not allowed');
        }

        $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user
        ));

        if($kyc){
            $kyc->setEmailValidated(true);
            $em->persist($kyc);
            $em->flush();
        }

        $response = array(
            'username'  =>  $user->getUsername(),
            'email'     =>  $user->getEmail()
        );

        return $this->restV2(201,"ok", "Validation email succesfully", $response);
    }

    public function validatePhone(Request $request){
        if(!$request->request->has('phone') || $request->get('phone')==""){
            throw new HttpException(400, "Missing parameter 'phone'");
        }
        $phone = $request->get('phone');

        if(!$request->request->has('prefix') || $request->get('prefix')==""){
            throw new HttpException(400, "Missing parameter 'prefix'");
        }
        $prefix = $request->get('prefix');

        $phone = preg_replace("/[^0-9,.]/", "", $phone);
        $prefix = preg_replace("/[^0-9,.]/", "", $prefix);

        if(!$this->checkPhone($phone, $prefix)){
            throw new HttpException(400, "Incorrect phone or prefix number");
        }

        $phone_info = array(
            "prefix" => $prefix,
            "number" => $phone
        );

        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user
        ));

        if($kyc){
            $code = substr(Random::generateToken(), 0, 6);
            $kyc->setPhoneValidated(false);
            $kyc->setValidationPhoneCode(json_encode(array("code" => $code, "tries" => 0)));
            $this->sendSMS($prefix, $phone, "Code " . $code);
            $kyc->setPhone(json_encode($phone_info));
            $em->persist($kyc);
            $em->flush();
        }
        else{
            throw new HttpException(400, 'User without kyc information');
        }
        return $this->restV2(201,"ok", "Request successful", $kyc);
    }

    public function validatePhoneCode(Request $request){
        if(!$request->request->has('code') || $request->get('code')==""){
            throw new HttpException(400, "Missing parameter 'code'");
        }
        $code = $request->get('code');

        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user
        ));

        if($kyc){
            $validation_info = json_decode($kyc->getValidationPhoneCode(), true);
            $validation_code = $validation_info['code'];
            if($validation_info['tries']>4){
                throw new HttpException(400, 'Too many tries');
            }
            elseif($code == $validation_code){
                $kyc->setPhoneValidated(true);
                $em->persist($kyc);
                $em->flush();
            }
            else{
                $validation_info['tries']+=1;
                $kyc->setValidationPhoneCode(json_encode($validation_info));
                $em->persist($kyc);
                $em->flush();
                throw new HttpException(400, 'Incorrrect code');
            }
        }
        else{
            throw new HttpException(400, 'User without kyc information');
        }
        return $this->restV2(201,"ok", "Request successful", $kyc);
    }

    public function sendSMS($prefix, $number, $text){
        $twilio = $this->get('twilio.api');
        $twilio->account->messages->create(array(
            'To' => '+' . $prefix . $number,
            'From' => "+14158141829",
            'Body' => "Hey Jenny! Good luck on the bar exam! " . $text
        ));
    }

    public function checkPhone($phone, $prefix){
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
}
