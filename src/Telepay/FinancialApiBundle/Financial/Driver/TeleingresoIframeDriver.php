<?php

namespace Telepay\FinancialApiBundle\Financial\Driver;

use nusoap_client;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TeleingresoIframeDriver{

    private $url;
    private $currency;
    private $hash;
    private $merchant;

    function __construct($currency, $url, $hash, $merchant)
    {
        $this->currency = $currency;
        $this->url = $url;
        $this->hash = $hash;
        $this->merchant = $merchant;
    }

    public function createIssue($amount){

        //curl para pillar los datos
        $reference = $this->getReference();
//        $ch = curl_init($this->url.'?amount='.($amount/100).'&merchant='.$this->merchant.'&reference='.$reference);
//
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//
//        $contents = curl_exec($ch);
//
//        preg_match('/Order Number: +([A-Z]+[0-9]+)</', $contents, $code);
//        if($code[1]){
//
//            $response = array(
//                'TxtDescription'    =>  'Accepted',
//                'transaction_id'    =>  $reference,
//                'chargeId'  =>  $code[1],
//                'track' =>  $reference
//            );
//        }else{
//            $response = array(
//                'TxtDescription'    =>  'Error'
//            );
//        }

        //el track equivale al reference
        $response = array(
            'TxtDescription'    =>  'Accepted',
            'transaction_id'    =>  $reference,
            'chargeId'  =>  '',
            'track' =>  $reference,
            'merchant'  =>  $this->merchant,
            'amount'    =>  $amount
        );

        return $response;

    }

    private function getReference(){
        $chars = "ABCDEFGHJKLMNPQRSTUVWXYZ123456789";

        $array_chars = str_split($chars);
        shuffle($array_chars);

        return substr(implode("", $array_chars),0,5);
    }

    function notification($params){

        //hash para calcular el md5  3D8Fc10xeA
        $calculated_md5 = md5($params['reference'] . $params['amount'] . $this->hash);

        if($params['md5'] == $calculated_md5){

            $response = array(
                'status'    =>  1,
                'response'  =>  'OKKY'
            );

        }else{
            $response = array(
                'status'    =>  1,
                'response'  =>  'OKKY'
            );
        }

        return $response;

    }

}