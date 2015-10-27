<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs;

use Symfony\Component\HttpKernel\Exception\HttpException;

class SepaIn{

    private $iban;
    private $bic_swift;

    function __construct($iban, $bic_swift)
    {
        $this->iban = $iban;
        $this->bic_swift = $bic_swift;
    }

    public function request(){

        $reference = $this->getReference();

        $response = array(
            'reference' =>  $reference,
            'iban'      =>  $this->iban,
            'bic_swift' =>  $this->bic_swift
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
