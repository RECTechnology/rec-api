<?php

namespace Telepay\FinancialApiBundle\Financial\Driver;

use nusoap_client;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TeleingresoUSADriver{

    private $login;
    private $password;
    private $certificateType;
    private $url;
    private $currency;
    private $hash;

    function __construct($login, $password, $certificateType, $currency, $url, $hash)
    {
        $this->login = $login;
        $this->password = $password;
        $this->certificateType = $certificateType;
        $this->currency = $currency;
        $this->url = $url;
        $this->hash = $hash;
    }

    public function createIssue($amount){

        //TODO curl para pillar los datos
        return $this->getReference();

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