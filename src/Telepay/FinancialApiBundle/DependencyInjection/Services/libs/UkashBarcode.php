<?php

//namespace Paynet;

require_once('includes/nusoap.php');

class UkashBarcode{

	var $brandId='UKASH17740';
    var $user='UKASH_Telepay';
    var $password='gdf58a4rtdut5tdy';
    var $merchant_id;
    var $currency;
    var $fecha;
    var $certificado;
    var $sRequest;
    var $transaction_id;
    var $amount;

	function __construct(){

	}

	public function request($merchant_id,$currency,$transaction_id,$amount){
        $this->merchant_id=$merchant_id;
        $this->currency=$currency;
        $this->transaction_id=$transaction_id;
        $this->amount=$amount;

        //comprobar importe sea decimal
        if(strpos($amount, ".")==false){
            $this->amount=$amount.".00";
        }

        //fecha UKASH
        $this->fecha = gmdate("Y-m-d H:i:s", time() + 7200); //Spain = GMT+1
        //EUR by default
        if ($currency==""){
            $this->currency="EUR";
            $this->certificado="6337180111043747226";
        }
        elseif($currency=="GBP"){
            $this->certificado="6337180019312117080";
        }
        elseif($currency=="MXN"){
            $this->certificado="6337185383663709381";
        }

        $this->sRequest = <<<XML
    <UKashTransaction>
        <ukashLogin>UKASH_Telepay</ukashLogin>
        <ukashPassword>gdf58a4rtdut5tdy</ukashPassword>
        <transactionId>$this->transaction_id</transactionId>
        <brandId>UKASH17740</brandId>
        <voucherNumber>$this->certificado</voucherNumber>
        <voucherValue>$this->amount</voucherValue>
        <baseCurr>$this->currency</baseCurr>
        <ticketValue></ticketValue>
        <redemptionType>4</redemptionType>
        <merchDateTime>$this->fecha</merchDateTime>
        <merchCustomValue>Paywin MXN Issuing</merchCustomValue>
        <storeLocationId>$this->merchant_id</storeLocationId>
        <amountReference></amountReference>
        <ukashNumber></ukashNumber>
        <ukashPin></ukashPin>
    </UKashTransaction>
XML;

        $params = array('sRequest' => $this->sRequest);

        $url='https://processing.ukash.com/gateway/Ukash.WSDL';

        $client = new nusoap_client($url, true);

        $result = $client -> call('IssueVoucher',$params);

        $transaction = new SimpleXMLElement($result['IssueVoucherResult']);

        $transaction=get_object_vars($transaction);

        return $transaction;


	}

}

