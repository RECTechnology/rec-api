<?php

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs;

/**
	* 
	*/
	class SafetyPayment
	{
		var $api_key;
		var $signature_key;
		var $merchant_reference;
		var $lang;
		var $tracking_code;
		var $expiration;
		var $response_format;
		var $date_time;
		var $currency;
		var $amount;
		var $url_success;
		var $url_error;
		var $url_safety;

		function __construct($api_key, $signature_key, $merchant_reference, $lang, $tracking_code, $expiration, $response_format, $url_safety)
		{
			$this->api_key = $api_key;
			$this->signature_key = $signature_key;
			$this->merchant_reference = $merchant_reference;
			$this->lang = $lang;
			$this->tracking_code = $tracking_code;
			$this->expiration = $expiration;
			$this->response_format = $response_format;
			$this->url_safety = $url_safety;
		}

		public function request($date_time, $currency, $amount, $url_success, $url_error){

			$this->date_time = $date_time;
			$this->currency = $currency;
			$this->amount = $amount;
			$this->url_success = $url_success;
			$this->url_error = $url_error;

			$ch = curl_init($this->url_safety);
			curl_setopt ($ch, CURLOPT_POST, 1);

			$data = $this->date_time.$this->currency.$this->amount.$this->merchant_reference.$this->lang.$this->tracking_code.$this->expiration.$this->url_success.$this->url_error.$this->signature_key;

            $signature = hash('sha256', $data,false);

			$params = array(
				'ApiKey'				=>	$this->api_key,
				'RequestDateTime'		=>	$this->date_time,
				'CurrencyCode'			=>	$this->currency,
				'Amount'				=>	$this->amount,
				'MerchantReferenceNo'	=>	$this->merchant_reference,
				'Language'				=>	$this->lang,
				'TrackingCode'			=>	'',
				'ExpirationTime'		=>	$this->expiration,
				'TransactionOkUrl'		=>	$this->url_success,
				'TransactionErrorUrl'	=>	$this->url_error,
				'ResponseFormat'		=>	$this->response_format,
				'Signature'				=>	$signature
			);

			curl_setopt ($ch, CURLOPT_POSTFIELDS, $params);
	 
			//le decimos que queremos recoger una respuesta (si no esperas respuesta, ponlo a false)
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			//recogemos la respuesta
			$respuesta = curl_exec ($ch);

			//o el error, por si falla
			$error = curl_error($ch);
	 
			//y finalmente cerramos curl
			curl_close ($ch);

            $res = explode(',',$respuesta);

			if($error){

				return $error;
			}elseif($res[0] != 0){

                $response['error_number'] = $res[0];
                $response['res1'] = $res[1];
                $response['res2'] = $res[2];
                $response['res3'] = $res[3];

            }else{
                $response['error_number'] = $res[0];
                $response['url'] = $res[2];
                $response['signature'] = $res[3];

			}
            return $response;

		}
	}

