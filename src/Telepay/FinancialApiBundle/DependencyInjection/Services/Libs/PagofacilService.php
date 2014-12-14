<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Services\Libs;

/**
	* 
	*/
	class PagofacilService
	{
		var $sucursal;
		var $usuario;
		var $servicio;
		var $name;
		var $surname;
		var $card_number;
		var $cvt;
		var $cp;
		var $expiration_month;
		var $expiration_year;
		var $amount;
		var $mail;
		var $phone;
		var $mobile_phone;
		var $street;
		var $colony;
		var $city;
		var $quarter;
		var $country;
		var $transaction_id;
		var $pedido;
        var $url_flag;
		
		function __construct($sucursal,$usuario,$servicio,$url_flag)
		{
			$this->sucursal=$sucursal;
			$this->usuario=$usuario;
			$this->servicio=$servicio;
            $this->url_flag=$url_flag;
		}

		public function request($name,$surname,$card_number,$cvt,$cp,$expiration_month,$expiration_year,$amount,$mail,$phone,$mobile_phone,$street,$colony,$city,$quarter,$country,$transaction_id)
        {
			
			$this->name=$name;
			$this->surname=$surname;
			$this->card_number=$card_number;
			$this->cvt=$cvt;
			$this->cp=$cp;
			$this->expiration_month=$expiration_month;
			$this->expiration_year=$expiration_year;
			$this->amount=$amount;
			$this->mail=$mail;
			$this->phone=$phone;
			$this->mobile_phone=$mobile_phone;
			$this->street=$street;
			$this->colony=$colony;
			$this->city=$city;
			$this->quarter=$quarter;
			$this->country=$country;
			$this->transaction_id=$transaction_id;

			$data = array(
				'idServicio' 		=> urlencode($this->servicio),
				'idSucursal' 		=> urlencode($this->sucursal),
				'idUsuario' 		=> urlencode($this->usuario),
				'nombre' 			=> urlencode($this->name),
				'apellidos' 		=> urlencode($this->surname),
				'numeroTarjeta' 	=> urlencode($this->card_number),
				'cvt' 				=> urlencode($this->cvt),
				'cp' 				=> urlencode($this->cp),
				'mesExpiracion' 	=> urlencode($this->expiration_month),
				'anyoExpiracion'	=> urlencode($this->expiration_year),
				'monto'	 			=> urlencode($this->amount),
				'email' 			=> urlencode($this->mail),
				'telefono' 			=> urlencode($this->phone),
				'celular' 			=> urlencode($this->mobile_phone),
				'calleyNumero' 		=> urlencode($this->street),
				'colonia' 			=> urlencode($this->colony),
				'municipio' 		=> urlencode($this->city),
				'estado' 			=> urlencode($this->quarter),
				'pais' 				=> urlencode($this->country),
				'idPedido' 			=> urlencode($this->transaction_id)
			);

            //Montamos la cadena
			$cadena='';
			foreach ($data as $key=>$valor){
				$cadena.="&data[$key]=$valor";
			}
            //var_dump($cadena);
            //Elegimos la url de Test o Producción
            if($this->url_flag=='test'){
                $url ='https://www.pagofacil.net/st/public/Wsrtransaccion/index/format/json/?method=transaccion'.$cadena;
            }elseif($this->url_flag=='prod'){
                $url ='https://www.pagofacil.net/ws/public/Wsrtransaccion/index/format/json/?method=transaccion'.$cadena;
            }

            //Curl
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			// Blindly accept the certificate
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($ch);
			curl_close($ch);

            //Montamos la respuesta
			$response = json_decode($response,true);
			//var_dump($response);
			if (isset($response['error'])) {
                $result['authorization']=$response['autorizado'];
                $result['text']=$response['tetxo'];
                $result['error']=$response['error'];
				return $result;
			}else{
				$response=$response['WebServices_Transacciones']['transaccion'];
				$valido = $response['autorizado'];
				if($valido==1){
                    $result['authorization']=$response['autorizado'];
                    $result['authorization_id']=$response['autorizacion'];
                    $result['transaction_id']=$response['transaccion'];
                    $result['text']=$response['texto'];
                    $result['mode']=$response['mode'];
                    $result['type_card']=$response['TipoTC'];
					return $result;
				}else{
                    $result['authorization']=$response['autorizado'];
                    $result['text']=$response['texto'];
                    $result['error']=$response['error'];
                    return $result;
				}
			}
		}

		public function status($transaction_id)
        {

			$this->transaction_id=$transaction_id;

			$data = array(
				'idSucursal' 		=> urlencode($this->sucursal),
				'idUsuario' 		=> urlencode($this->usuario),
				'idPedido' 			=> urlencode($this->transaction_id)
			);

            //Montamos la cadena
			$cadena='';
			foreach ($data as $key=>$valor){
				$cadena.="&data[$key]=$valor";
			}

            //Seleccionamos la url de Test o Producción
            if($this->url_flag=='test'){
			    $url ='https://www.pagofacil.net/st/public/Wsrtransaccion/index/format/json/?method=verificar'.$cadena;
            }elseif($this->url_flag=='prod'){
                $url ='https://www.pagofacil.net/ws/public/Wsrtransaccion/index/format/json/?method=verificar'.$cadena;
            }

            //Curl
            $ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			// Blindly accept the certificate
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($ch);
			curl_close($ch);

            //Montamos la respuesta
            $response = json_decode($response,true);
            //die(print_r($response,true));
			if (isset($response['WebServices_Transacciones']['verificar']['error'])) {
				return $response;
			}else{
				$response=$response['WebServices_Transacciones']['verificar'];

				$valido = $response['autorizado'];
				if($valido==1){
                    unset($response['data']);
					return $response;
				}else{
                    $result['authorization']=$response['autorizado'];
                    $result['text']=$response['texto'];
                    $result['error']=$response['error'];
                    return $result;
				}
			}
		}


	}

