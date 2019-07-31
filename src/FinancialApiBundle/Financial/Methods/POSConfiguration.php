<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 4:42 AM
 */

namespace App\FinancialApiBundle\Financial\Methods;

class POSConfiguration {

    private $type;
    private $allowed_currencies_in;
    private $allowed_currencies_out;
    private $currency;
    private $default_currency_out;

    public function __construct($type, $currency, $default_currency, $allowed_currencies_in, $allowed_currencies_out){
        $this->type = $type;
        $this->allowed_currencies_in = $allowed_currencies_in;
        $this->allowed_currencies_out = $allowed_currencies_out;
        $this->currency = $currency;
        $this->default_currency_out = $default_currency;

    }

    public function getInfo(){

        $currencies_in = explode('/', $this->allowed_currencies_in);
        $currencies_out = explode('/', $this->allowed_currencies_out);
        $response = array(
            'type' =>  $this->type,
            'allowed_currencies_in' =>  $currencies_in,
            'allowed_currencies_out' =>  $currencies_out,
            'currency'  =>  $this->currency,
            'default_currency'  =>  $this->default_currency_out

        );

        return $response;
    }

}