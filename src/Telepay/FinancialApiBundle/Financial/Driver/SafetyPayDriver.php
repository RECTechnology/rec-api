<?php
namespace Telepay\FinancialApiBundle\Financial\Driver;

use Symfony\Component\HttpKernel\Exception\HttpException;

class SafetyPayDriver{

    private $api_key;
    private $signature_key;
    private $lang;
    private $expiration;
    private $response_format;
    private $date_time;
    private $currency;
    private $amount;
    private $url_success;
    private $url_error;
    private $url_safety;
    private $tracking_code;

    function __construct($api_key, $signature_key, $language, $tracking_code, $response_format, $url_safety)
    {
        $this->api_key = $api_key;
        $this->signature_key = $signature_key;
        $this->lang = $language;
        $this->tracking_code = $tracking_code;
        $this->response_format = $response_format;
        $this->url_safety = $url_safety;
        $this->date_time = $this->_getDateIso8601(time());
        $this->expiration = 15;
    }

    function request($currency, $amount){
        $merchant_reference = $this->getReference();
        $this->currency = $currency;
        $this->amount = ($amount/100);

        $this->url_success = 'https://web.chip-chap.com/ok/'.$merchant_reference;
        $this->url_error = 'https://web.chip-chap.com/ko/'.$merchant_reference;

        $ch = curl_init($this->url_safety);
        curl_setopt ($ch, CURLOPT_POST, 1);

        $data = $this->date_time.$this->currency.$this->amount.$merchant_reference.$this->lang.$this->tracking_code.$this->expiration.$this->url_success.$this->url_error.$this->signature_key;
        $signature = hash('sha256', $data,false);

        $params = array(
            'ApiKey'				=>	$this->api_key,
            'RequestDateTime'		=>	$this->date_time,
            'CurrencyCode'			=>	$this->currency,
            'Amount'				=>	$this->amount,
            'MerchantSalesID'	    =>	$merchant_reference,
            'Language'				=>	$this->lang,
            'TrackingCode'			=>	$this->tracking_code,
            'ExpirationTime'		=>	$this->expiration,
            'TransactionOkURL'		=>	$this->url_success,
            'TransactionErrorURL'	=>	$this->url_error,
            'ProductID'             =>  '1',
            'ResponseFormat'        =>  $this->response_format,
            'Signature'				=>	$signature
        );

        curl_setopt ($ch, CURLOPT_POSTFIELDS, $params);

        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

        $respuesta = curl_exec ($ch);

        $error = curl_error($ch);

        curl_close ($ch);

        if($error){
            throw new HttpException(400, $error);
        }else{
            $res = explode(',',$respuesta);
            if($res[0] != 0){

                $response['error_number'] = $res[0];
                $response['res1'] = $res[1];
                $response['res2'] = $res[2];
                $response['res3'] = $res[3];

            }else{
                $response['error_number'] = $res[0];
                $response['responseDateTime'] = $res[1];
                $response['url'] = $res[2];
                $response['signature'] = $res[3];

                //TODO comprobar la signature para saber si la respuesta es legitima
                $dataToSign = $response['responseDateTime'].$response['url'].$this->signature_key;
                $signatureResponse = hash('sha256', $dataToSign,false);

                if(strtoupper($signatureResponse) != $response['signature']) throw new HttpException(403, 'Bad response');

            }

            if($response['error_number'] != 0){
                $message = '';
                switch($response['error_number']){
                    case 100100:
                        $message = 'General Error.';
                        break;
                    case 100101:
                        $message = 'Service not allowed';
                        break;
                    case 100102:
                        $message = 'Currency code error';
                        break;
                    case 100103:
                        $message = 'Currency not allowed';
                        break;
                    case 100104:
                        $message = 'Error number of digits';
                        break;
                    case 100105:
                        $message = 'Amount must be > 0';
                        break;
                    case 2:
                        $message = 'Bad Signature';
                        break;
                }
                $response['error_description'] = $message;
                throw new HttpException(400,'Transaction failed - '.$message);

            }
            //TODO check Signature
            $paymentInfo = array();
            $paymentInfo['url'] = $response['url'];
            $paymentInfo['expires_in'] = $this->expiration * 60;
            $paymentInfo['reference'] = $merchant_reference;

            return $paymentInfo;
        }

    }

    function notification($params){

        $dataToSign = $params['RequestDateTime'].$params['MerchantSalesID'].$params['ReferenceNo'].$params['CreationDateTime'].$params['Amount'].$params['CurrencyID'].$params['PaymentReferenceNo'].$params['Status'].$this->signature_key;
        $signature = hash('sha256', $dataToSign,false);

        if(strtoupper($signature) == $params['Signature']){
            if($params['Status'] == 102){
                $response = array(
                    'status'    =>  1,
                    'params'    =>  $params
                );
            }else{
                $response = array(
                    'status'    =>  0,
                    'params'    =>  'error code'
                );
            }

        }else{
            $response = array(
                'status'    =>  0,
                'params'    =>  $dataToSign
            );
        }

        return $response;

    }

    private function getReference(){
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

        $array_chars = str_split($chars);
        shuffle($array_chars);

        return substr(implode("", $array_chars),0,5);
    }

    private function _getDateIso8601( $int_date )
    {
        $date_mod = date('Y-m-d\TH:i:s', $int_date);
        $pre_timezone = date('O', $int_date);
        $time_zone = substr($pre_timezone, 0, 3) . ':'
            . substr($pre_timezone, 3, 2);
        $pos = strpos($time_zone, "-");
        if (PHP_VERSION >= '4.0')
            if ($pos === false) {
                // nothing
            }
            else
                if ($pos != 0)
                    $date_mod = $time_zone;
                else
                    if (is_string($pos) && !$pos) {
                        // nothing
                    }
                    else
                        if ($pos != 0)
                            $date_mod = $time_zone;

        return $date_mod;
    }

}