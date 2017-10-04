<?php
namespace Telepay\FinancialApiBundle\Financial\Driver;

use nusoap_client;
use soapclient;
use SoapVar;

class PaynetReferenceDriver{

    var $client_reference;
    var $amount;
    var $description;
    var $issuer;
    var $caducity;
    var $resultado;
    var $url;

    function __construct($issuer, $url)
    {
        $this->issuer = $issuer;
        $this->url = $url;
    }

    public function request($client_reference, $amount, $description){
        $this->client_reference = $client_reference;
        $this->amount = $amount;
        $this->description = $description;
        $caducity = $this->getCaducity();

//        $parameters = array(
//            'Reference' =>  $this->client_reference,
//            'Monto' =>  $this->amount/100,
//            'fechaVig'  =>  $caducity
//        );

//        $params = array(
//            'issuerCod'         =>  $this->issuer,
//            'clientReference' 	=>  $this->client_reference,    //'000532603689',
//            'amount' 			=>  $this->amount/100,              //'00001000',
//            'dueDate' 			=> 	$caducity,
//            'description' 		=> 	$this->description          //'television'
//        );
//        $params = array(
//            'issuerCod'         =>  $this->issuer,
//            'params'    =>  $parameters,
//            'description' 		=> 	$this->description
//        );
//
//        $client = new nusoap_client($this->url, true);

//        $result = $client -> call('GetPaynetReference',$params);

        //################       NEW CODE         #################
        $client = new SoapClient($this->url, array('soap_version'   => SOAP_1_2,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'trace'     => 1
        ));

        $XMLSchema = "http://www.w3.org/2001/XMLSchema";
        $baseParams = array();
        $baseParams[] = new SoapVar($this->issuer, XSD_STRING, null, null, 'ns1:issuerCod' );
        $baseParams[] = new SoapVar($description, XSD_STRING, null, null, 'ns1:description' );

        $params = array();
        $params[] = new SoapVar(array(    new SoapVar('Reference', XSD_STRING, null, null, 'ns1:Name'),
            new SoapVar($client_reference, XSD_STRING, "long", $XMLSchema, "ns1:Value")),
            SOAP_ENC_OBJECT, null, null, 'ns1:Parameter');

        $params[] = new SoapVar(array(    new SoapVar('Amount', XSD_STRING, null, null, 'ns1:Name'),
            new SoapVar($this->amount/100, XSD_STRING, "string", $XMLSchema, 'ns1:Value')),
            SOAP_ENC_OBJECT, null, null, 'ns1:Parameter');

        $params[] = new SoapVar(array(    new SoapVar('DueDate', XSD_STRING, null, null, 'ns1:Name'),
            new SoapVar($caducity, XSD_STRING, "string", $XMLSchema, 'ns1:Value')),
            SOAP_ENC_OBJECT, null, null, 'ns1:Parameter');

        $baseParams[] = new SoapVar($params, SOAP_ENC_OBJECT, null, null, 'ns1:params');


        // EJECUTA LLAMADO AL METODO GetPaynetReference DEL WS
        $result = $client->GetPaynetReference(new SoapVar($baseParams, SOAP_ENC_OBJECT));

        if($result->GetPaynetReferenceResult->RespCode == "0"){
            $code = $result->GetPaynetReferenceResult->PaynetReference;
            $resultado = array(
                'id'                =>  $this->client_reference,
                'amount'            =>  $this->amount,
                'expiration_date'   =>  $caducity,
                'description'       =>  $this->description,
                'barcode'           =>  $code,
                'barcode_url'       =>  'https://api.openpay.mx/barcode/' . $code . '?width=1&height=45',
                'error_code'    =>  0
            );
            return $resultado;
        }
        else{
            $resultado['error_code'] = $result->GetPaynetReferenceResult->RespCode;
            $resultado['error_description'] = $result->GetPaynetReferenceResult->RespDesc;
            return $resultado;
        }
    }

    public function getCaducity(){
        //Calcular caducidad
        $dia = date('d')+15;
        $mes = date('m');
        $ano = date('Y');
        $ultimo = strftime("%d", mktime(0, 0, 0, $mes+1, 0, $ano));
        if($dia>$ultimo){
            $dia = $dia-$ultimo;
            $mes = $mes+1;
        }
        if($mes>12){
            $mes = "01";
            $ano = $ano+1;
        }
        if(strlen($dia) == 1){
            $dia="0".$dia;
        }
        if(strlen($mes) == 1){
            $mes="0".$mes;
        }
        $caducity = $ano."-".$mes."-".$dia;
        //    <Value>2014-07-13</Value>
        return $caducity;
    }

    public function status($client_reference){

        $this->client_reference = $client_reference;

        $params = array(
            'issuerCod'         =>  $this->issuer,
            'clientReference' 	=>  $this->client_reference
        );

        $client = new nusoap_client($this->url, true);

        $result = $client -> call('GetPaynetReferenceStatus',$params);

        if($result->GetPaynetReferenceStatusResult->RespCode == "0"){
            $resultado = $result->GetPaynetReferenceStatusResult->Status;
            $error = 0;
            $description = 0;
            $resultArray = array(
                'error_code'        =>  $error,
                'error_description' =>  $description,
                'status_code'       =>  $resultado
            );

            switch ($resultado) {
                case 0:
                    $resultArray['status_description'] = 'Printed';
                    $resultArray['status'] = 'created';
                    return $resultArray;
                    break;
                case 1:
                    $resultArray['status_description'] = 'Pending';
                    $resultArray['status'] = 'created';
                    return $resultArray;
                    break;
                case 2:
                    $resultArray['status_description'] = 'Authorized';
                    $resultArray['status'] = 'success';
                    return $resultArray;
                    break;
                case 3:
                    $resultArray['status_description'] = 'Canceled';
                    $resultArray['status'] = 'cancelled';
                    return $resultArray;
                    break;
                case 4:
                    $resultArray['status_description'] = 'Reversed';
                    $resultArray['status'] = 'refund';
                    return $resultArray;
                    break;
                case 5:
                    $resultArray['status_description'] = 'Reserved';
                    $resultArray['status'] = 'review';
                    return $resultArray;
                    break;
                case 6:
                    $resultArray['status_description'] = 'Revision';
                    $resultArray['status'] = 'review';
                    return $resultArray;
                    break;
                default:
                    $resultArray['status_description'] = 'Unexpected error';
                    $resultArray['status'] = 'error';
                    return $resultArray;
                    break;
            }
        }else{
            $resultado = $result->GetPaynetReferenceStatusResult->RespDesc;
            $error_code = $result->GetPaynetReferenceStatusResult->RespCode;
            $resultArray = array(
                'error_code'        =>  $error_code,
                'error_description' =>  $resultado
            );
            return $resultArray;
        }
    }
}