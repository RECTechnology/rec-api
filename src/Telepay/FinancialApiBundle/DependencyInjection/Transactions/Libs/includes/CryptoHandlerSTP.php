<?php

function signInfo($pemFile, $dataToSign, $passphrase){
	//die(print_r($dataToSign,true));

	$pKeyId = getCertified($pemFile, $passphrase);

	$binary_signature = "";
	//echo '<h3>data to sign</h3>';
	//print $dataToSign."<p/>";

        $hex_ary = array();
        foreach (str_split($dataToSign) as $chr) {
            $hex_ary[] = sprintf("%02X", ord($chr));
        }
        //echo '<h3>retVal</h3>';
	//echo implode(' ',$hex_ary);
	
	openssl_sign($dataToSign, $binary_signature, $pKeyId, "RSA-SHA256");
	$retVal = base64_encode($binary_signature);

	openssl_free_key($pKeyId);

	return $retVal;
}

function getCertified($pemFile, $passphrase){
//die(print_r(__DIR__.$pemFile,true));
	$fp = fopen(__DIR__."/".$pemFile, "r");
	$private_key = fread($fp, 8192);
	fclose($fp);

	return openssl_get_privatekey($private_key, $passphrase);
}


