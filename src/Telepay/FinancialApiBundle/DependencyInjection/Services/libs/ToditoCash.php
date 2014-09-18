<?php
	
	ini_set("default_socket_timeout", 5);
	ini_set("soap.wsdl_cache_enabled", 0);
	
	class ToditoCash{

		var $contract_id;
		var $transaction_id;
		var $branch_id;
		var $date;
		var $hour;
		var $card_number;
		var $nip;
		var $tc_transaction_id;
		var $amount;
		var $concept;
		var $currency;
		var $production_flag;
        var $response;
        var $arrayResponse=array();
		
		function __construct($contract_id,$branch_id){
			$this->contract_id=$contract_id;
			$this->branch_id=$branch_id;
		}

		public function request($transaction_id,$date,$hour,$card_number,$nip,$amount,$concept,$currency,$production_flag){

			$this->transaction_id=$transaction_id;
			$this->date=$date;
			$this->hour=$hour;
			$this->card_number=$card_number;
			$this->nip=$nip;
			$this->amount=$amount;
			$this->concept=$concept;
            $this->currency=$currency;
			$this->production_flag=$production_flag;

            try {

			    $client = new SoapClient('http://200.23.37.104:3636/appr_tc/?wsdl');
                //$client = new SoapClient('http://localhost:3636/appr_tc/?wsdl');
	  
			    $request = new SoapVar(array(
                        'idContrato'	=>$this->contract_id,       //2801,
                        'idTran'		=>$this->transaction_id, 	//1,
                        'idSucursal'	=>$this->branch_id, 		//"test",
                        'fecha'			=>$this->date, 			    //"2014-04-21",
                        'hora'			=>$this->hour, 				//"01:49:40",
                        'numTarjeta'	=>$this->card_number, 		//'1111111111',
                        'nip'			=>$this->nip, 				//'3333',
                        'monto'			=>$this->amount, 			//'10',
                        'concepto'		=>$this->concept,			//'Pago test Telepay',
                        'divisa'		=>$this->currency, 			//'MXN',
                        'banProd'		=>$this->production_flag 	//'0',
                        ),SOAP_ENC_OBJECT);

			    $response = $client->pagoTC($request);
                $response=get_object_vars($response);
                $response=$response['pagoTCResult'];
                $response=get_object_vars($response);

                $resultado['transaction_id']=$response['idTran'];
                $resultado['tc_transaction_id']=$response['idTranTodito'];
                $resultado['date']=$response['fecha'];
                $resultado['hour']=$response['hora'];
                $resultado['card_number']=$response['numTarjeta'];
                $resultado['balance']=$response['saldo'];
                $resultado['change_type']=$response['tipoCambio'];
                $resultado['status']=$response['status'];
                $resultado['status_message']=$response['status_msg'];

            }catch (SoapFault $e){
				$error['error']= 'Hubo un error '.$e;
				return $error;  
			}//catch
            return $resultado;

		}

		public function reverso($tc_transaction_id,$transaction_id,$date,$hour,$card_number,$amount,$production_flag){

			$this->tc_transaction_id=$tc_transaction_id;
			$this->transaction_id=$transaction_id;
			$this->date=$date;
			$this->hour=$hour;
			$this->card_number=$card_number;
			$this->amount=$amount;
			$this->production_flag=$production_flag;

            try{
			    //$client = new SoapClient('http://200.23.37.104:3636/appr_tc/?wsdl');
                $client = new SoapClient('http://localhost:3636/appr_tc/?wsdl');
	  
			    $request = new SoapVar(array(
                        'idContrato'	=>$this->contract_id,		//2801,
                        'idTran'		=>$this->transaction_id, 	//1,
                        'idSucursal'	=>$this->branch_id, 		//"test",
                        'fecha'			=>$this->date, 			    //"2014-04-21",
                        'hora'			=>$this->hour, 				//"01:49:40",
                        'numTarjeta'	=>$this->card_number, 		//'1111111111',
                        'numTransTC'	=>$this->tc_transaction_id, //'3333',
                        'monto'			=>$this->amount, 			//'10',
                        'banProd'		=>$this->production_flag 	//'0',
                        ),SOAP_ENC_OBJECT);

			    $response = $client->pagoTC($request);
                $response=get_object_vars($response);
                $response=get_object_vars($response['pagoTCResult']);
                //die(print_r($response,true));

                $resultado=array();
                $resultado['transaction_id']=$response['idTran'];
                $resultado['tc_transaction_id']=$response['idTranTodito'];
                $resultado['date']=$response['fecha'];
                $resultado['hour']=$response['hora'];
                $resultado['card_number']=$response['numTarjeta'];
                $resultado['balance']=$response['saldo'];
                $resultado['change_type']=$response['tipoCambio'];
                $resultado['status']=$response['status'];
                $resultado['status_message']=$response['status_msg'];
			  
			}catch (SoapFault $e){  
				$error= 'Hubo un error '.$e;
				return $error;  
			}//catch  
			  
			return $resultado;

		}
	}
