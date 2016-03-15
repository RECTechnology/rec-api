<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs;

use Symfony\Component\HttpKernel\Exception\HttpException;

class EasyPay{

    private $account;

    function __construct($account)
    {
        $this->account = $account;
    }

    public function request(){

        $reference = 'BUY BITCOIN '.$this->getReference();

        $response = array(
            'reference' =>  $reference,
            'account'      =>  $this->account
        );

        return $response;

    }

    private function getReference(){
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

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
