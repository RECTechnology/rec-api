<?php

	require_once('includes/nusoap.php');

	class PaynetGetStatus{

		var $client_reference;
		var $issuer='467accc5-4051-4472-bb17-dad1f68779bf';
		
		function __construct(){

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
