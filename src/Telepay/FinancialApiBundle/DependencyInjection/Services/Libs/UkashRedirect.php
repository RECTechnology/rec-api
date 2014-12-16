<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Services\Libs;

class UkashRedirect{

		private $total;
        private $merchant_trans_id;
        private $merchant_currency;
        private $consumer_id;
		private $url_succes;
		private $url_fail;
		private $url_notification;
        private $mode;
        private $utid;

		function __construct($mode){
            $this->mode=$mode;
		}

		public function request($total,$merchant_trans_id,$consumer_id,$merchant_currency,$url_succes,$url_fail,$url_notification){

            $this->url_succes=$url_succes;
            $this->url_fail=$url_fail;
            $this->url_notification=$url_notification;
            $this->total=$total;
            $this->merchant_trans_id=$merchant_trans_id;
            $this->merchant_currency=$merchant_currency;
            $this->consumer_id=$consumer_id;

            //die(print_r($merchant_currency,true));

            function DoHttpPost($URL,$ArrayOfPostData){
                /**
                 * Building QUERY (Including URL Encode)
                 * or $postData = urlencode('s_Request='.$xml);
                 */
                $postData = http_build_query($ArrayOfPostData);

                /**
                 * Preparing Header
                 * strlen | mb_strlen
                 */
                $headA = array();
                $headA[] = "Content-Type: application/x-www-form-urlencoded";
                $headA[] = "Content-Length: ".mb_strlen($postData);

                /**
                 * POST using CURL
                 */
                $ch = curl_init();
                curl_setopt($ch,CURLOPT_URL,$URL);
                curl_setopt($ch,CURLOPT_POST, TRUE);
                curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
                curl_setopt($ch,CURLOPT_POSTFIELDS,$postData);
                curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
                curl_setopt($ch,CURLOPT_HEADER, false);
                curl_setopt($ch,CURLOPT_HTTPHEADER,$headA);
                $responseA = curl_exec($ch);
                curl_close ($ch);

                /**
                 * OUTPUT
                 */
                return $responseA;
            }

            function url_decode($string){
                return utf8_decode(urldecode($string));
            }

			$DataToPost = array(
				'SecurityToken' => 			'12345678901234567890',
				'BrandID' => 				'UKASH10082',
				'LanguageCode'=> 			'EN',
				'MerchantTransactionID'=> 	''.$this->merchant_trans_id.'', //'123',
				'MerchantCurrency'=> 		''.$this->merchant_currency.'', //'GBP',
				'ConsumerID'=> 				''.$this->consumer_id.'',       //'123',
				'URL_Success'=>				''.$this->url_succes.'',        //'https://direct.staging.ukash.com/candystore/success.aspx',
				'URL_Fail'=> 				''.$this->url_fail.'',          //'https://direct.staging.ukash.com/candystore/failure.aspx',
				'URL_Notification'=> 		''.$this->url_notification.'',  //'http://direct.staging.ukash.com/candystore/Notification.aspx',
				'TransactionValue'=> 		''.$this->total.''
			);

            if ($this->mode=='T'){
			    //Post call to the RPP Gateway, an soap request will be returned.
			    $XmlResult =  (DoHttpPost('https://processing.staging.ukash.com/RPPGateway/Process.asmx/GetUniqueTransactionID',$DataToPost));
            }elseif($this->mode='P'){
                //Post call to the RPP Gateway, an soap request will be returned.
                $XmlResult =  (DoHttpPost('https://processing.ukash.com/RPPGateway/Process.asmx/GetUniqueTransactionID',$DataToPost));
            }else{
                //Devolvemos un error porque el parámetro mode no es correcto
            }

			//Convert the string value to XML
			$xml = new SimpleXmlElement($XmlResult);
			
			//Decode the xml strings value
			$decodedstring = url_decode($xml);
			
			//Reloaded the decoded string, as XML.
			$xml = new SimpleXmlElement($decodedstring);

			//Extract the UTID from the XML object
            $node1 = $xml->xpath('/UKashRPP/SecurityToken');
			$node2 = $xml->xpath('/UKashRPP/UTID');
            $node3 = $xml->xpath('/UKashRPP/errCode');
            $node4 = $xml->xpath('/UKashRPP/errDescription');

            $values=array();

            //die(print_r($xml,true));


            $errCode=(string)$node3[0];

            if($errCode==0){
                $security=(string)$node1[0];
                $UTID = (string)$node2[0];
                //$values['security_token']=$security;
                $values['utid']=$UTID;
                $values['error_number']=$errCode;
            }else{
                $errDescription=(string)$node4[0];

                $values['error_number']=$errCode;
                $values['error_description']=$errDescription;
            }

			//Return the UTID value to the calling function.
			return $values;

		}

        public function status($utid){
            $this->utid=$utid;



            function DoHttpPost($URL,$ArrayOfPostData){
                /**
                 * Building QUERY (Including URL Encode)
                 * or $postData = urlencode('s_Request='.$xml);
                 */
                $postData = http_build_query($ArrayOfPostData);

                /**
                 * Preparing Header
                 * strlen | mb_strlen
                 */
                $headA = array();
                $headA[] = "Content-Type: application/x-www-form-urlencoded";
                $headA[] = "Content-Length: ".mb_strlen($postData);

                /**
                 * POST using CURL
                 */
                $ch = curl_init();
                curl_setopt($ch,CURLOPT_URL,$URL);
                curl_setopt($ch,CURLOPT_POST, TRUE);
                curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
                curl_setopt($ch,CURLOPT_POSTFIELDS,$postData);
                curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
                curl_setopt($ch,CURLOPT_HEADER, false);
                curl_setopt($ch,CURLOPT_HTTPHEADER,$headA);
                $responseA = curl_exec($ch);
                curl_close ($ch);

                /**
                 * OUTPUT
                 */
                return $responseA;
            }

            function url_decode($string){
                return utf8_decode(urldecode($string));
            }

            $DataToPost = array(
                'SecurityToken' => 		'12345678901234567890',
                'BrandID'       => 		'UKASH10082',
                'UTID'          => 		''.$this->utid.''
            );

            if ($this->mode=='T'){
                //Post call to the RPP Gateway, an soap request will be returned.
                $XmlResult =  (DoHttpPost('https://processing.staging.ukash.com/RPPGateway/Process.asmx/GetTransactionStatus',$DataToPost));
            }elseif($this->mode='P'){
                //Post call to the RPP Gateway, an soap request will be returned.
                $XmlResult =  (DoHttpPost('https://processing.ukash.com/RPPGateway/Process.asmx/GetTransactionStatus',$DataToPost));
            }else{
                //Devolvemos un error porque el parámetro mode no es correcto
            }

            //Convert the string value to XML
            $xml = new SimpleXmlElement($XmlResult);

            //Decode the xml strings value
            $decodedstring = url_decode($xml);

            //Reloaded the decoded string, as XML.
            $xml = new SimpleXmlElement($decodedstring);

            //Extract the parameters
            $node1 = $xml->xpath('/UKashRPP/SecurityToken');
            $node2 = $xml->xpath('/UKashRPP/UTID');
            $node3 = $xml->xpath('/UKashRPP/TransactionCode');
            $node4 = $xml->xpath('/UKashRPP/TransactionDesc');
            $node5 = $xml->xpath('/UKashRPP/MerchantTransactionID');
            $node6 = $xml->xpath('/UKashRPP/SettleAmount');
            $node7 = $xml->xpath('/UKashRPP/MerchantCurrency');
            $node8 = $xml->xpath('/UKashRPP/UkashTransactionID');
            $node9 = $xml->xpath('/UKashRPP/errCode');
            $node10 = $xml->xpath('/UKashRPP/errDescription');

            $values=array();

            $errCode=(string)$node9[0];

            if($errCode==0){
                $security=(string)$node1[0];
                $values['utid'] = (string)$node2[0];
                $values['trans_code']=(string)$node3[0];
                $values['trans_desc']=(string)$node4[0];
                $values['merchant_id']=(string)$node5[0];
                $values['amount']=(string)$node6[0];
                $values['currency']=(string)$node7[0];
                $values['ukash_trans_id']=(string)$node8[0];

            }else{
                $errDescription=(string)$node10[0];
                $values['error_code']=$errCode;
                $values['error_description']=$errDescription;
            }

            return $values;

        }
	}
