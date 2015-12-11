<?php

namespace Telepay\FinancialApiBundle\Financial\Driver;

use Symfony\Component\HttpKernel\Exception\HttpException;

class SepaDriver{

    function __construct()
    {

    }

    public function send(){}

    public function check(){}

    public function validateiban($iban){

        return true;

    }

    public function validatebic($bic){

        return true;

    }


}