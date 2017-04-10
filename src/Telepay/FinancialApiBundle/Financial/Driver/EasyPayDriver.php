<?php
namespace Telepay\FinancialApiBundle\Financial\Driver;

use Symfony\Component\HttpKernel\Exception\HttpException;

class EasyPayDriver{

    private $account;

    function __construct($account)
    {
        $this->account = $account;
    }

    public function request(){

        $reference = $this->getReference();

        $response = array(
            'reference_code' =>  $reference,
            'account'      =>  $this->account,
            'expires_in'=>  3600*24
        );

        return $response;

    }

    private function getReference(){
        $chars = "ABCDEFGHJKMNPQRSTUVWXYZ23456789";

        $array_chars = str_split($chars);
        shuffle($array_chars);

        return substr(implode("", $array_chars),0,5);
    }

    function getInfo(){
        $response = array(
            'account_number' =>  $this->account
        );

        return $response;
    }

}