<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs;

//namespace Paynet;

use nusoap_client;

require_once('includes/nusoap.php');

class PaynetGetBarcode{

	var $client_reference;
	var $amount;
	var $description;
	var $issuer;
	var $caducity;
	var $resultado;

    function __construct($issuer)
    {
        $this->issuer = $issuer;
    }

    public function request($client_reference,$amount,$description){
        $this->client_reference=$client_reference;
        $this->amount=$amount;
        $this->description=$description;
        $caducity=$this->getCaducity();

		$params = array(
	        'issuerCod'         =>  $this->issuer,
	        'clientReference' 	=>  $this->client_reference,    //'000532603689',
	        'amount' 			=>  $this->amount,              //'00001000',
	        'dueDate' 			=> 	$caducity,
			'description' 		=> 	$this->description          //'television'
    	);

    	$client = new nusoap_client('http://201.147.99.51/PaynetCE/WSPaynetCE.asmx?WSDL', true);
		
		$result = $client -> call('GetPaynetReference',$params);

		if($result['GetPaynetReferenceResult']['RespCode']=="0"){
            $code=$result['GetPaynetReferenceResult']['PaynetReference'];
            $resultado=array(
                'id'                =>  $this->client_reference,
                'amount'            =>  $this->amount*100,
                'expiration_date'   =>  $caducity,
                'description'       =>  $this->description,
                'barcode'           =>  $code
            );
			return $resultado;
		}
		else{
            $resulatdo['error_code']=$result['GetPaynetReferenceResult']['RespCode'];
			$resultado['error_description']=$result['GetPaynetReferenceResult']['RespDesc'];
			return $resultado;
		} 
	}

	public function getCaducity(){
		//Calcular caducidad
		$dia=date('d')+15;
		$mes=date('m');
		$ano=date('Y');
		$ultimo=strftime("%d", mktime(0, 0, 0, $mes+1, 0, $ano)); 
		if($dia>$ultimo){
			$dia=$dia-$ultimo;
			$mes=$mes+1;
		}
		if($mes>12){
			$mes="01";
			$ano=$ano+1;
		}
		if(strlen($dia)==1){
			$dia="0".$dia;
		}
		if(strlen($mes)==1){
			$mes="0".$mes;
		}
		$caducity=$ano."-".$mes."-".$dia;
		//    <Value>2014-07-13</Value>
		return $caducity;
	}

    public function status($client_reference){
        $this->client_reference=$client_reference;
        $params = array(
            'issuerCod'         =>  $this->issuer,
            'clientReference' 	=>  $this->client_reference
        );

        $client = new nusoap_client('http://201.147.99.51/PaynetCE/WSPaynetCE.asmx?WSDL', true);

        $result = $client -> call('GetPaynetReferenceStatus',$params);

        if($result['GetPaynetReferenceStatusResult']['RespCode']=="0"){
            $resultado=$result['GetPaynetReferenceStatusResult']['Status'];
            $error=0;
            $description=0;
            $resultArray=array(
                'error_code'        =>  $error,
                'error_description' =>  $description,
                'status_code'       =>  $resultado
            );

            switch ($resultado) {
                case 0:
                    $resultArray['status_description']='Printed';
                    return $resultArray;
                    break;
                case 1:
                    $resultArray['status_description']='Pending';
                    return $resultArray;
                    break;
                case 2:
                    $resultArray['status_description']='Authorized';
                    return $resultArray;
                    break;
                case 3:
                    $resultArray['status_description']='Canceled';
                    return $resultArray;
                    break;
                case 4:
                    $resultArray['status_description']='Reversed';
                    return $resultArray;
                    break;
                case 5:
                    $resultArray['status_description']='Reserved';
                    return $resultArray;
                    break;
                case 6:
                    $resultArray['status_description']='Revision';
                    return $resultArray;
                    break;
                default:
                    $resultArray['status_description']='Unexpected error';
                    return $resultArray;
                    break;
            }
        }else{
            $resultado=$result['GetPaynetReferenceStatusResult']['RespDesc'];
            $error_code=$result['GetPaynetReferenceStatusResult']['RespCode'];
            $resultArray=array(
                'error_code'    =>  $error_code,
                'error_description' =>  $resultado
            );
            return $resultArray;
        }
    }

}

