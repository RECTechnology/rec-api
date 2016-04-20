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
        $this->date_time = date('Y-m-d\Th:i:s');
        $this->url_success = 'http://playa-almarda.es';
        $this->url_error = 'http://pasproduccions.com';
        $this->expiration = 120;
    }

    function request($currency, $amount){

        $merchant_reference = $this->getReference();
        $this->currency = $currency;
        $this->amount = $amount/100;

        $ch = curl_init($this->url_safety);
        curl_setopt ($ch, CURLOPT_POST, 1);

        $data = $this->date_time.$this->currency.$this->amount.$merchant_reference.$this->lang.$this->tracking_code.$this->expiration.$this->url_success.$this->url_error.$this->signature_key;
//        $data = '2016-03-29T11:21:01PEN15.00CM1943ES120https://www.safetypay.comhttps://www.safetypay.com'
        $signature = hash('sha256', $data,false);

        $params = array(
            'ApiKey'				=>	$this->api_key,
            'RequestDateTime'		=>	$this->date_time,
            'CurrencyID'			=>	$this->currency,
            'Amount'				=>	$this->amount,
            'MerchantSalesID'	    =>	$merchant_reference,
            'Language'				=>	$this->lang,
            'TrackingCode'			=>	$this->tracking_code,
            'ExpirationTime'		=>	$this->expiration,
            'TransactionOkURL'		=>	$this->url_success,
            'TransactionErrorURL'	=>	$this->url_error,
            'ResponseFormat'		=>	$this->response_format,
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
                $response['url'] = $res[2];
                $response['signature'] = $res[3];

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

            return $paymentInfo;
        }

    }

    function notification($params){

        $received_api_key = $params['ApiKey'];
        $date_time = $params['RequestDateTime'];
        $merchant = $params['MerchantReferenceNo'];
        $received_signature = $params['Signature'];

        $calculated_signature = '';

        if($received_api_key == $this->api_key && $received_api_key == $calculated_signature){
            $response = array(
                'status'    =>  1,
                'params'    =>  $params
            );
        }else{
            $response = array(
                'status'    =>  0,
                'params'    =>  $params
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

}