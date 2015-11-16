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
    private $url;

    function __construct($user, $password, $alias, $url)
    {
        $this->alias = $alias;
        $this->user = $user;
        $this->password = $password;
        $this->url = $url;
        if($user === 'fake') $this->mode = 'T';
    }

    public function ticker($country){

        $params = array(
            'usuario'		=>	$this->user,
            'contrasenia'	=>	$this->password,
            'pais'          =>  $country
        );

        $url = $this->url.'/HalCashGatewayIssue.asmx?wsdl';
        $client = new nusoap_client($url,true);

        $response = $client->call("Precio",$params);

        if ($client->fault) { // Si

            //$response='error3';
        } else { // No

            $sError = $client->getError();

            // Hay algun error ?
            if ($sError) { // Si
                throw new HttpException(400,"No se pudo completar la operacion [".$sError."]");
                //$response=$sError;
            }
        }

        $response = $response['PrecioResult']['precio'];
        $precio = preg_replace('/,/','.',$response);
        $precio = doubleval($precio)/100.0;

        return $precio;

    }

}