<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs;

use Symfony\Component\HttpKernel\Exception\HttpException;

class BankTransfer{

    function __construct()
    {
    }

    public function request(){

        $reference = $this->getReference();

        $response = array(
            'reference' =>  $reference
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
