<?php

namespace Telepay\FinancialApiBundle\Controller;

use Services_Twilio_TinyHttp;
use Services_Twilio;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\OAuthServerBundle\Util\Random;
use Telepay\FinancialApiBundle\Entity\KYC;
use Telepay\FinancialApiBundle\Entity\TierValidations;
use Telepay\FinancialApiBundle\Entity\User;

class KycController extends BaseApiController{

    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:User";
    }

    function getNewEntity()
    {
        return new User();
    }

    public function registerAction(Request $request){
        if(!$request->request->has('company') || $request->get('company')==""){
            $company = "chipchap";
        }
        else{
            $company = $request->get('company');
        }
        $request->request->remove('company');

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
        $request->request->add(array('email'=>$email));
        $request->request->add(array('enabled'=>1));
        $request->request->add(array('base64_image'=>''));
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

            $tokenGenerator = $this->container->get('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());
            $em->persist($user);
            $em->flush();
            if($company == "holytransaction"){
                $url = "https://holytransaction.trade/";
                $url = $url.'?user_token='.$user->getConfirmationToken();
                $this->_sendEmail('Holy Transaction validation e-mail', $url, $user->getEmail(), 'register_kyc_holy');
            }
            else{
                $url = $this->container->getParameter('web_app_url');
                $url = $url.'?user_token='.$user->getConfirmationToken();
                $this->_sendEmail('Chip-Chap validation e-mail', $url, $user->getEmail(), 'register_kyc');
            }


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
        $user = $this->get('security.token_storage')->getToken()->getUser();
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
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $url = $this->container->getParameter('web_app_url');
        $tokenGenerator = $this->container->get('fos_user.util.token_generator');
        $user->setConfirmationToken($tokenGenerator->generateToken());
        $em->persist($user);
        $em->flush();
        $url = $url.'?user_token='.$user->getConfirmationToken();
        $this->_sendEmail('Chip-Chap validation e-mail', $url, $user->getEmail(), 'register_kyc');
        return $this->restV2(201,"ok", "Request successful", $user->getEmail());
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

        if($request->request->has('user')){
            $user = $request->request->get('user');
        }else{
            $user = $this->get('security.token_storage')->getToken()->getUser();
        }
        $em = $this->getDoctrine()->getManager();
        $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user
        ));

        if($kyc){
            $code = substr(Random::generateToken(), 0, 6);
            $kyc->setPhoneValidated(false);
            $kyc->setValidationPhoneCode(json_encode(array("code" => $code, "tries" => 0)));
            $this->sendSMS($prefix, $phone, "Chip-chap Code " . $code);
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
        $em = $this->getDoctrine()->getManager();

        if(!$request->request->has('NIF') || $request->get('NIF')=="") {
            $user = $this->get('security.token_storage')->getToken()->getUser();
        }
        else{
            $user = $em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array(
                'username' => $request->get('NIF')
            ));
            if(!$user){
                throw new HttpException(400, "CIF not registered");
            }
        }

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
                $user->setEnabled(true);
                $em->persist($user);
                $em->flush();
            }
            else{
                $validation_info['tries']+=1;
                $kyc->setValidationPhoneCode(json_encode($validation_info));
                $em->persist($kyc);
                $em->flush();
                throw new HttpException(400, 'Incorrect code');
            }
        }
        else{
            throw new HttpException(400, 'User without kyc information');
        }
        return $this->restV2(201,"ok", "Request successful", $kyc);
    }

    private function sendSMS($prefix, $number, $text){
        $sid = $this->container->getParameter('twilio_sid');
        $token = $this->container->getParameter('twilio_authToken');
        $from = $this->container->getParameter('twilio_from');
        $http = new Services_Twilio_TinyHttp(
            'https://api.twilio.com',
            array('curlopts' => array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 2,
            ))
        );

        $twilio = new Services_Twilio($sid, $token, "2010-04-01", $http);
        $twilio->account->messages->create(array(
            'To' => "+" . $prefix . $number,
            'From' => $from,
            'Body' => $text,
        ));
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

    private function _sendEmail($subject, $body, $to, $action){

        $from = 'no-reply@chip-chap.com';
        $mailer = 'mailer';
        if($action == 'register'){
            $template = 'TelepayFinancialApiBundle:Email:registerconfirm.html.twig';
        }elseif($action == 'recover'){
            $template = 'TelepayFinancialApiBundle:Email:recoverpassword.html.twig';
        }elseif($action == 'register_kyc'){
            $template = 'TelepayFinancialApiBundle:Email:registerconfirmkyc.html.twig';
        }elseif($action == 'register_kyc_holy'){
            $template = 'TelepayFinancialApiBundle:Email:registerconfirmkycholy.html.twig';
            $from = 'no-reply@holytransaction.trade';
            $mailer = 'swiftmailer.mailer.holy_mailer';
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
