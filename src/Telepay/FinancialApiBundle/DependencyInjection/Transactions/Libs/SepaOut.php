<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs;

class SepaOut{

    function __construct()
    {
    }

    public function request($amount, $iban, $beneficiary, $concept){

        $response = array(
            'authorization_code' =>  99,
            'message'   =>  'You will receive the transfer during the next 24h'
        );

        return $response;

    }

    function getReference(){
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

        $array_chars = str_split($chars);
        shuffle($array_chars);

        return substr(implode("", $array_chars),0,5);
    }

}
