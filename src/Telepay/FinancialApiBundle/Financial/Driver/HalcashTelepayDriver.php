<?php

namespace Telepay\FinancialApiBundle\Financial\Driver;

use nusoap_client;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HalcashTelepayDriver{

    private $prefix;
    private $mode;
    private $phone;
    private $amount;
    private $reference;
    private $pin;
    private $alias;
    private $transaction_id;
    private $hal;
    private $user;
    private $password;
    private $language;

    function __construct($user, $password, $alias)
    {
        $this->alias = $alias;
        $this->user = $user;
        $this->password = $password;
        if($user === 'fake') $this->mode = 'T';
    }

    public function ticker($country){

        $precio = 0.2389;

        return $precio;

    }

}