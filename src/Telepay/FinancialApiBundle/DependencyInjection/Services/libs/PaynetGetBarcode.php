<?php

//namespace Paynet;

require_once('includes/nusoap.php');

class PaynetGetBarcode{

	var $client_reference;
	var $amount;
	var $description;
	var $issuer='467accc5-4051-4472-bb17-dad1f68779bf';
	var $caducity;
	var $resultado;

	function __construct(){

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
                'client_reference'  =>  $this->client_reference,
                'amount'            =>  $this->amount,
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

}

