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

//    public function ticker($country){
//
//        $params = array(
//            'usuario'		=>	$this->user,
//            'contrasenia'	=>	$this->password,
//            'pais'          =>  $country
//        );
//
//        $url = $this->url.'/HalCashGatewayIssue.asmx?wsdl';
//
//        $client = new nusoap_client($url,true);
//
//        $response = $client->call("Precio",$params);
//        if ($client->fault) { // Si
//
//        } else { // No
//
//            $sError = $client->getError();
//
//            // Hay algun error ?
//            if ($sError) { // Si
//                throw new HttpException(400,"No se pudo completar la operacion [".$sError."]");
//            }
//        }
//
//        $response = $response['PrecioResult']['precio'];
//        $precio = preg_replace('/,/','.',$response);
//        $precio = doubleval($precio)/100.0;
//
//        return $precio;
//
//    }

    public function ticker($country){

        $precio = 0.2389;

        return $precio;

    }

    public function sendV3($phone,$prefix,$amount,$reference,$pin){

        $this->phone = $phone;
        $this->prefix = $prefix;
        $this->amount = $amount;
        $this->reference = $reference;
        $this->pin = $pin;
        $caducity = gmdate("Y-m-d",time()+604800);

        $params = array(
            'usuario'		=>	$this->user,
            'contrasenia'	=>	$this->password,
            'prefijo'		=>	$this->prefix,
            'telefono'		=>	$phone,
            'importe'		=>	$amount,
            'concepto'		=>	$reference,
            'pin'			=>	$pin,
            'aliascuenta'	=>	'ASOC ROBOT',
            'caducidad'		=>	$caducity
        );

        if($this->mode == 'T'){
            $response = array(
                'errorcode'=>'0',
                'halcashticket'=>'1234567890'
            );

        }else{
            $url = $this->url.'/HalCashGatewayIssue.asmx?wsdl';
            $client = new nusoap_client($url, true, false, false, false, false, 5, 60);
            if ($sError = $client->getError()) {
                throw new HttpException(503,"No se pudo completar la operacion [".$sError."]");
            }
            $response = $client->call("Emision",$params);
            if ($client->fault) { // Si
                throw new HttpException(503,"No se pudo completar la operacion [".$sError."]");
            } else { // No
                $sError = $client->getError();
                // Hay algun error ?
                if ($sError) { // Si
                    throw new HttpException(503,"No se pudo completar la operacion [".$sError."]");
                }
            }
        }

        $response = $response['EmisionResult'];

        return $response;

    }

    public function sendInternational($phone,$prefix,$amount,$reference,$pin, $country,$language){

        $this->phone = $phone;
        $this->prefix = $prefix;
        $this->amount = $amount;
        $this->reference = $reference;
        $this->pin = $pin;
        $this->country = $country;
        $caducity = gmdate("Y-m-d",time()+604800);

        $params = array(
            'usuario'		=>	$this->user,
            'contrasenia'	=>	$this->password,
            'prefijo'		=>	$this->prefix,
            'telefono'		=>	$phone,
            'importe_destino'=>	$amount,
            'pais'          =>  $country,
            'idioma'        =>  $language,
            'concepto'		=>	$reference,
            'pin'			=>	$pin,
            'aliascuenta'	=>	'ASOC ROBOT',
            'caducidad'		=>	$caducity
        );

        if($this->mode == 'T'){
            $response = array(
                'errorcode'=>'0',
                'halcashticket'=>'1234567890'
            );

        }else{
            $url = $this->url.'/HalCashGatewayIssue.asmx?wsdl';
            $client = new nusoap_client($url, true, false, false, false, false, 5, 60);
            if ($sError = $client->getError()) {
                throw new HttpException(400,"No se pudo completar la operacion [".$sError."]");
            }
            $response = $client->call("EmisionInternacional",$params);
            if ($client->fault) { // Si
                throw new HttpException(400,"No se pudo completar la operacion [".$sError."]");
            } else { // No
                $sError = $client->getError();
                // Hay algun error ?
                if ($sError) { // Si
                    throw new HttpException(400,"No se pudo completar la operacion [".$sError."]");
                }
            }

            $response = $response['EmisionInternacionalResult'];
        }

        return $response;

    }

    public function cancelation($ticket,$reference){

        $this->reference = $reference;
        $this->hal = $ticket;

        $params = array(
            'usuario'		=>	$this->user,
            'contrasenia'	=>	$this->password,
            'ticket'        =>  $this->hal,
            'motivo'		=>	$reference
        );


        if($this->mode == 'T'){
            $response = array(
                'errorcode'=>'0'
            );
            //$response='error1';

        }else{
            $url = $this->url.'/HalCashGatewayIssue.asmx?wsdl';
            $client = new nusoap_client($url,true);
            if ($sError = $client->getError()) {
                throw new HttpException(400,"No se pudo completar la operacion [".$sError."]");
                //$response='error2';
            }
            $response = $client->call("Cancelacion",$params);
            if ($client->fault) { // Si
                throw new HttpException(400,"No se pudo completar la operacion [".$sError."]");
                //$response='error3';
            } else { // No
                $sError = $client->getError();
                // Hay algun error ?
                if ($sError) { // Si
                    throw new HttpException(400,"No se pudo completar la operacion [".$sError."]");
                    //$response=$sError;
                }
            }
            $response = $response['CancelacionResult'];
        }

        return $response;

    }

    public function status($ticket){

        $this->hal = $ticket;

        $params = array(
            'usuario'		=>	$this->user,
            'contrasenia'	=>	$this->password,
            'ticket'        =>  $this->hal
        );

        if($this->mode == 'T'){
            $response = array(
                'errorcode'=>'0',
                'status'=>'Autorizada'
            );
            //$response='error1';

        }else{
            $url = $this->url.'/HalCashGatewayIssue.asmx?wsdl';
            $client = new nusoap_client($url,true);
            if ($sError = $client->getError()) {
                throw new HttpException(400,"No se pudo completar la operacion [".$sError."]");
                //$response='error2';
            }
            $response = $client->call("Estado",$params);
            if ($client->fault) { // Si
                throw new HttpException(400,"No se pudo completar la operacion [".$sError."]");
                //$response='error3';
            } else { // No
                $sError = $client->getError();
                // Hay algun error ?
                if ($sError) { // Si
                    throw new HttpException(400,"No se pudo completar la operacion [".$sError."]");
                    //$response=$sError;
                }
            }

            $response = $response['EstadoResult'];
        }



        return $response;

    }


}