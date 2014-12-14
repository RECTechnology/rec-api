<?php

namespace Telepay\FinancialApiBundle\DependencyInjection\Services\Libs;

require_once 'lib/PayU.php';

	/**
	* 
	*/
	class PayUReport
	{
		var $report_Type;
		var $reference_code;
		var $transaction_id;
        var $order_id;
		
		function __construct($report_Type)
		{
			$this->report_Type=$report_Type;
		}

		public function report_by_order_id($order_id){
			$this->order_id=$order_id;

			$parameters = array(
				PayUParameters::ORDER_ID => $this->order_id
			);
			$response = PayUReports::getOrderDetail($parameters);
			//var_dump($parameters);
			return ($response);
		}
		public function report_by_reference($reference_code){
			$this->reference_code=$reference_code;

			//Ingresa aquí el código de referencia de la orden.
			$parameters = array(
				PayUParameters::REFERENCE_CODE => $this->reference_code
				);
			$response = PayUReports::getOrderDetailByReferenceCode($parameters);
            $response=get_object_vars($response[0]);
            //var_dump($response);
			return ($response);
		}
		public function report_by_transaction_id($transaction_id){
			$this->transaction_id=$transaction_id;

			$parameters = array(
				PayUParameters::TRANSACTION_ID => $this->transaction_id
			);
			$response = PayUReports::getTransactionResponse($parameters);
			return ($response);
		}
	}
