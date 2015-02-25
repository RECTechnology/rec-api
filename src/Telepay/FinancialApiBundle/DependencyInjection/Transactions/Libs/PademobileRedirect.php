<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs;

class PademobileRedirect{

        private $mode;
        var $client='299249';
        var $tp_url;

		function __construct($mode){
            $this->mode=$mode;
		}

		public function request($country,$tx,$description,$amount){
            $this->country=$country;
            $this->description=$description;
            $this->amount=$amount;

            if($this->mode=='T'){
                $pm_url = 'https://staging.pademobile.com:700/comprar';
                $this->tp_url=$tx;
            }else{
                $pm_url = 'https://www.pademobile.com/comprar';
                $this->tp_url=$tx;
            }

            $pm_params = array(
                'pais' => $country,
                'id_usuario' => $this->client,
                'descripcion' => $description,
                'importe' => $amount,
                'url' => $this->tp_url
            );

            $pm_params_string = '';

            // Convert array to post values in url format
            foreach ($pm_params as $key => $value) {
                $pm_params_string .= $key . '=' . urlencode($value) . '&';
            }

            // Remove extra '&' from end of string
            $pm_params_string = rtrim($pm_params_string, '&');

            // Generate signature hash
            $private_key = 'i9839l6php8u21y'; // API Key proporcionada
            $firma = hash_hmac('sha1', $pm_params_string, $private_key);
            //die(print_r($firma));

            // Append signature hash to end of request string
            $pm_params_string.='&firma=' . $firma;

            // OUTPUT IMPLEMENTATION
            $output = $pm_url . '?' . $pm_params_string;

            return $output;

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
