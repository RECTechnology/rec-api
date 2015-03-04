<?php

    namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs;

    require_once('includes/class.HALManager.php');

	class HalcashServiceMx{

        private $mode;
        private $phone;
        private $amount;
        private $reference;
        private $pin;
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

            $hal=new HALManager();

            $hal->cargaentradaalta($this->phone, $this->amount, $this->reference, $this->pin, $this->transaction_id );

            if($this->mode=='T'){
                $hal->enviadatosTest( $hal->servicios('ALTA') );
                //die(print_r($hal,true));
            }else{
                $hal->enviadatos( $hal->servicios('ALTA') );
            }

            return $hal->getresultado();

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
