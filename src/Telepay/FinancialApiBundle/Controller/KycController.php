<?php

namespace Telepay\FinancialApiBundle\Controller;

use Telepay\FinancialApiBundle\Controller\RestApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class KycController extends RestApiController{

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
        $message = $twilio->account->messages->sendMessage(
            '9991231234', // From a valid Twilio number
            '+' . $prefix . $number, // Text this number
            $text
        );

        print $message->sid;
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
