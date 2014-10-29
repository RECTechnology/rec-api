<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Services;

use Symfony\Component\HttpKernel\Exception\HttpException;
use SafetyPayment;

//Include the class
include("libs/SafetyPayment.php");

class Safetypay{

    //This parameters are unique for us. Don't give to the client
    //For Test
    private $testArray =array(
        'api_key'           =>  '247acc3167b49419634fe3b87e8623ef',
        'signature_key'     =>  '43b4da81e4a4fc2a1c1f1b45d53bf577',
        'merchant_reference'=>  '5339',
        'language'          =>  'ES',
        'tracking_code'     =>  '',
        'expiration_time'   =>  '5',
        'response_format'   =>  'CSV',
        'url_safety'        =>  'https://mws2.safetypay.com/Sandbox/express/post/v.2.2/CreateExpressToken.aspx'
    );

    //For production
    private $prodArray =array(
        'api_key'           =>  '8fc4c5ae3994b549709b741e37bf5500',
        'signature_key'     =>  'bbe1cb9502d4a8fae6bcfa08014e0433',
        'merchant_reference'=>  '5339',
        'language'          =>  'ES',
        'tracking_code'     =>  '',
        'expiration_time'   =>  '5',
        'response_format'   =>  'CSV',
        'url_safety'        =>  'https://mws2.safetypay.com/express/post/v.2.2/CreateExpressToken.aspx'
    );

    public function getSafetypayTest(){

        return new SafetyPayment(
            $this->testArray['api_key'],
            $this->testArray['signature_key'],
            $this->testArray['merchant_reference'],
            $this->testArray['language'],
            $this->testArray['tracking_code'],
            $this->testArray['expiration_time'],
            $this->testArray['response_format'],
            $this->testArray['url_safety'],
            $this->testArray['signature_key']
        );
    }

    public function getSafetypay(){

        return new SafetyPayment(
            $this->prodArray['api_key'],
            $this->prodArray['signature_key'],
            $this->prodArray['merchant_reference'],
            $this->prodArray['language'],
            $this->prodArray['tracking_code'],
            $this->prodArray['expiration_time'],
            $this->prodArray['response_format'],
            $this->prodArray['url_safety'],
            $this->prodArray['signature_key']
        );

    }

}