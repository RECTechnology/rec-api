<?php

namespace Telepay\FinancialApiBundle\Financial\Driver;

use nusoap_client;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TeleingresoDriver{

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

        $transactionId = $this->getReference();
        $params = array(
            'Login' => $this->login,
            'Password' => $this->password,
            'transactionId' => $transactionId,
            'certificateType' => $this->certificateType,
            'track' => $transactionId,
            'key' => $transactionId,
            'certificateValue' => $amount,
            'currency' => $this->currency
        );

        $client = new nusoap_client($this->url, true);
        if ($sError = $client->getError()) {
            throw new HttpException(503,"No se pudo completar la operacion [".$sError."]");
        }
        $response = $client->call("issue",$params);
        if ($client->fault) { // Si
            throw new HttpException(503,"No se pudo completar la operacion [".$sError."]");
        } else { // No
            $sError = $client->getError();
            // Hay algun error ?
            if ($sError) { // Si
                throw new HttpException(503,"No se pudo completar la operacion [".$sError."]");
            }
        }

        $response = simplexml_load_string($response);

        $json_string = json_encode($response);

        $result_array = json_decode($json_string, TRUE);

        return $result_array;

    }

    private function getReference(){
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

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