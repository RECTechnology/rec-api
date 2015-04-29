<?php
//require_once('nusoap.php');
require_once('CryptoHandlerSTP.php');
include('STPLib.php');

function registraOrden($data, $pemFile, $passphrase){

	$dataToSign = $data->_getDataToSign("UTF-8");

	//echo $dataToSign.'<br>';
  	$retVal = signInfo($pemFile, $dataToSign, $passphrase);
	//echo "<br>retVal :<BR>$retVal<br><br>";
    //die(print_r($dataToSign,true));
  	$data->set_firma($retVal);
  	//echo "<br>value :<BR>".$data->get_empresa()."<br><br>";

	$curl = curl_init();
	
	$ordenPago = '<soapenv:Envelope
xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:h2h="http://h2h.integration.spei.enlacefi.lgec.com/">
   <soapenv:Header/>
   <soapenv:Body>
      <h2h:registraOrden>
         <ordenPago>
            <claveRastreo>'.$data->get_claveRastreo().'</claveRastreo>
            <conceptoPago>'.$data->get_conceptoPago().'</conceptoPago>
            <cuentaBeneficiario>'.$data->get_cuentaBeneficiario().'</cuentaBeneficiario>
            <empresa>'.$data->get_empresa().'</empresa>
            <firma>'.$data->get_firma().'</firma>
            <institucionContraparte>'.$data->get_institucionContraparte().'</institucionContraparte>
            <institucionOperante>'.$data->get_institucionOperante().'</institucionOperante>
            <monto>'.$data->get_monto().'</monto>
            <nombreBeneficiario>'.$data->get_nombreBeneficiario().'</nombreBeneficiario>
            <referenciaNumerica>'.$data->get_referenciaNumerica().'</referenciaNumerica>
            <tipoCuentaBeneficiario>'.$data->get_tipoCuentaBeneficiario().'</tipoCuentaBeneficiario>
            <tipoPago>'.$data->get_tipoPago().'</tipoPago>
         </ordenPago>
      </h2h:registraOrden>
   </soapenv:Body>
</soapenv:Envelope>';

	//echo $ordenPago.'<br><br>';
	//die(print_r($ordenPago,true));
	$header = array(
		"Content-Type: text/xml;charset=UTF-8",
		"SOAPAction: ",
		"Content-Length: ".strlen($ordenPago));

	curl_setopt_array($curl, array(
		CURLOPT_SSLVERSION=>3,
		CURLOPT_SSL_VERIFYPEER=> FALSE,
	    CURLOPT_RETURNTRANSFER => 1,
	    CURLOPT_SSL_VERIFYHOST=>0,
	    //CURLOPT_URL => 'https://www.stpmex.com:7012/spei/webservices/SpeiServices?wsdl',
		CURLOPT_URL => 'https://demo.stpmex.com:7005/speidemo/webservices/SpeiServices?WSDL',
        //CURLOPT_URL => 'http://demo.stpmex.com:7004/speidemo/webservices/SpeiServices?WSDL',
	    CURLOPT_POST => 1,
		CURLOPT_POSTFIELDS => $ordenPago,
		CURLOPT_HTTPHEADER => $header,
		CURLOPT_TIMEOUT=>10,
		CURLOPT_CONNECTTIMEOUT=>10,
		CURLOPT_FORBID_REUSE=>1
	));

	//curl_easy_setopt($curl, CURLOPT_CAPATH, capath);
	
	

	$data = curl_exec($curl);
    $error = 0;
	if (curl_errno($curl)) 
	{ 	
		//$id_respuesta = -1;
		//print "Error: " . curl_error($curl);
        $error = curl_error($curl);

	} 
	else 
	{ 	//var_dump($data);
		//print_r($data);
		//echo '<br><br>';
		//echo $data;		
		//echo '<br><br>';
		//echo 'El resultado es: '.$data.'<br>';
		
		/*$id_respuesta =preg_replace("/[^0-9]/", "", $data);
		settype($id_respuesta, "int");
		
		echo 'Ahora es: '.$id_respuesta.'<br>';
		
		if ($id_respuesta == 0)
			$id_respuesta = 1000;	//solo para grabar en sistema si manda error*/
	} 
	
	curl_close($curl);

    $response = preg_match('/<return>.*<\/return>/',$data,$return);

    $xml = new SimpleXMLElement($return[0]);
    $res = get_object_vars($xml);
    if(isset($res['descripcionError'])){
        $spei_response = array(
            'error' =>  1,
            'description'   =>  $res['descripcionError'],
            'id'    =>  $res['id']
        );
    }else{
        $spei_response = array(
            'error' =>  0,
            'error_description'   =>  '',
            'id'    =>  $res['id']
        );
    }

    return($spei_response);
	
}