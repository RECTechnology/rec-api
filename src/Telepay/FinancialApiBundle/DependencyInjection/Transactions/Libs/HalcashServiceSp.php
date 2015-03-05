<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs;

    require_once('includes/class.HALManager.php');
    require_once('includes/nusoap.php');
    use nusoap_client;
    use Symfony\Component\HttpKernel\Exception\HttpException;

	class HalcashServiceSp{

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

        function __construct($user, $password, $alias)
        {
            $this->alias = $alias;
            $this->user = $user;
            $this->password = $password;
            if($user === 'fake') $this->mode = 'T';
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
                'prefijo'		=>	'34',
                'telefono'		=>	$phone,
                'importe'		=>	$amount,
                'concepto'		=>	$reference,
                'pin'			=>	$pin,
                'aliascuenta'	=>	'ASOC ROBOT',
                'caducidad'		=>	$caducity
            );


            if($this->mode=='T'){
                $response=array(
                    'errorcode'=>'0',
                    'halcashticket'=>'1234567890'
                );

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

            $response=$response['EmisionResult'];

            return $response;

        }

		public function sendV2($phone,$prefix,$amount,$reference,$pin,$transaction_id,$alias){

            $this->phone=$phone;
            $this->prefix=$prefix;
            $this->alias=$alias;
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
                $response=array(
                    'errorcode'=>'0',
                    'halcashticket'=>'1234567890'
                );

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

                $response=$response['EmisionResult'];
            }



            return $response;

        }

        public function sendV3($phone,$prefix,$amount,$reference,$pin,$transaction_id){

            $this->phone=$phone;
            $this->prefix=$prefix;
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
                'aliascuenta'	=>	'ASOC ROBOT',
                'caducidad'		=>	$caducity
            );

            if($this->mode=='T'){
                $response=array(
                    'errorcode'=>'0',
                    'halcashticket'=>'1234567890'
                );

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

            $response=$response['EmisionResult'];

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

        public function cancelation($ticket,$reference){

            $this->reference=$reference;
            $this->hal=$ticket;

            $params=array(
                'usuario'		=>	$this->user,
                'contrasenia'	=>	$this->password,
                'ticket'        =>  $this->hal,
                'motivo'		=>	$reference
            );


            if($this->mode=='T'){
                throw new HttpException(501,"Test mode not implemented");
                //$response='error1';

            }else{
                $url='http://hcsvc.telepay.net/HalCashGatewayIssue.asmx?wsdl';
                $client=new nusoap_client($url,true);
                if ($sError = $client->getError()) {
                    throw new HttpException(400,"No se pudo completar la operacion [".$sError."]");
                    //$response='error2';
                }
                $response=$client->call("Cancelacion",$params);
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

            $response=$response['CancelacionResult'];

            return $response;

        }

	}
