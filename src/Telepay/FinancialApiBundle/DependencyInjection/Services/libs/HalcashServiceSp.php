<?php

    require_once('includes/class.HALManager.php');
    require_once('includes/nusoap.php');

	class HalcashServiceSp{

        private $user='halcash';
        private $password="t3l3p4y";
        private $prefix='34';
        private $mode;
        private $phone;
        private $amount;
        private $reference;
        private $pin;
        private $alias='generica';
        private $transaction_id;
        private $hal;

		function __construct($mode){
            $this->mode=$mode;
		}

		public function send($phone,$amount,$reference,$pin,$transaction_id){

            $this->phone=$phone;
            $this->amount=$amount;
            $this->reference=$reference;
            $this->pin=$pin;
            $this->transaction_id=$transaction_id;
            $caducity=gmdate("Y-m-d",time()+604800);

            $params=array(
                'usuario'		=>	$this->user,
                'contrasenia'	=>	$this->password,
                'prefijo'		=>	$this->prefix,
                'telefono'		=>	$phone,
                'importe'		=>	$amount,
                'concepto'		=>	$reference,
                'pin'			=>	$pin,
                'aliascuenta'	=>	$this->alias,
                'caducidad'		=>	$caducity
            );


            if($this->mode=='T'){
                throw new HttpException(400,"Service unavailable");
                //$response='error1';

            }else{
                $url='http://hcsvc.telepay.net/HalCashGatewayIssue.asmx?wsdl';
                $client=new nusoap_client($url,true);
                if ($sError = $client->getError()) {
                    throw new HttpException(400,"No se pudo completar la operacion [".$sError."]");
                    //$response='error2';
                }
                $response=$client->call("Emision",$params);
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
            }

            return $response;

        }

        public function payment($phone,$amount,$reference,$pin,$transaction_id){

            $this->phone=$phone;
            $this->amount=$amount;
            $this->reference=$reference;
            $this->pin=$pin;
            $this->transaction_id=$transaction_id;

            $hal=new HALManager();

            $hal->cargaentradapago($this->phone, $this->amount, $this->reference, $this->pin, $this->transaction_id );

            if($this->mode=='T'){
                $hal->enviadatosTest( $hal->servicios('PAGO') );
                //die(print_r($hal,true));
            }else{
                $hal->enviadatos( $hal->servicios('PAGO') );
            }

            return $hal->getresultado();

        }

	}
