<?php

require_once('includes/nusoap.php');

class UkashBarcode{

    var $merchant_id;
    var $currency;
    var $fecha;
    var $certificado;
    var $sRequest;
    var $transaction_id;
    var $amount;
    var $voucher_number;
    var $voucher_value;
    var $transaction_amount;
    var $mode;

	function __construct($mode){
        $this->mode=$mode;
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
        <merchCustomValue></merchCustomValue>
        <storeLocationId>$this->merchant_id</storeLocationId>
        <amountReference></amountReference>
        <ukashNumber></ukashNumber>
        <ukashPin></ukashPin>
    </UKashTransaction>
XML;

        $params = array('sRequest' => $this->sRequest);

        if($this->mode=='T'){
            $url='https://processing.staging.ukash.com/gateway/Ukash.WSDL';
        }else{
            $url='https://processing.ukash.com/gateway/Ukash.WSDL';
        }

        $client = new nusoap_client($url, true);
        //die(print_r($params,true));
        $result = $client -> call('IssueVoucher',$params);

        $transaction = new SimpleXMLElement($result['IssueVoucherResult']);

        $transaction=get_object_vars($transaction);

        return $transaction;


	}

    public function redemption($merchant_id,$currency,$transaction_id,$voucher_value,$voucher_number,$transaction_amount){
        $this->merchant_id=$merchant_id;
        $this->currency=$currency;
        $this->transaction_id=$transaction_id;
        $this->voucher_value=$voucher_value;
        $this->voucher_number=$voucher_number;
        $this->transaction_amount=$transaction_amount;

        //comprobar importe sea decimal
        if(strpos($voucher_value, ".")==false){
            $this->voucher_value=$voucher_value.".00";
        }

        if(strpos($transaction_amount, ".")==false){
            $this->transaction_amount=$transaction_amount.".00";
        }

        //fecha UKASH
        $this->fecha = gmdate("Y-m-d H:i:s", time() + 7200); //Spain = GMT+1
        //EUR by default

        $this->sRequest = <<<XML
    <UKashTransaction>
        <ukashLogin>UKASH_Hipodromo</ukashLogin>
        <ukashPassword>2eZLGxWuP9NhQOIN</ukashPassword>
        <transactionId>$this->transaction_id</transactionId>
        <brandId>UKASH19450</brandId>
        <voucherNumber>$this->voucher_number</voucherNumber>
        <voucherValue>$this->voucher_value</voucherValue>
        <baseCurr>$this->currency</baseCurr>
        <ticketValue>$this->transaction_amount</ticketValue>
        <redemptionType>3</redemptionType>
        <merchDateTime>$this->fecha</merchDateTime>
        <merchCustomValue></merchCustomValue>
        <storeLocationId>$this->merchant_id</storeLocationId>
        <amountReference></amountReference>
        <ukashNumber></ukashNumber>
        <ukashPin></ukashPin>
    </UKashTransaction>
XML;

        $params = array('sRequest' => $this->sRequest);

        $url='http://telepay.net/wsdl/redemption.wsdl.xml';

        $client = new nusoap_client($url, true);

        $result = $client -> call('Redemption',$params);

        $transaction = new SimpleXMLElement($result['RedemptionResult']);

        $transaction=get_object_vars($transaction);

        return $transaction;


    }

    public function status($transaction_id,$currency,$voucher_value,$voucher_number,$transaction_amount){
        $this->transaction_id=$transaction_id;
        $this->currency=$currency;
        $this->voucher_value=$voucher_value;
        $this->voucher_number=$voucher_number;
        $this->transaction_amount=$transaction_amount;

        //comprobar importe sea decimal
        if(strpos($voucher_value, ".")==false){
            $this->voucher_value=$voucher_value.".00";
        }

        if(strpos($transaction_amount, ".")==false){
            $this->transaction_amount=$transaction_amount.".00";
        }

        //fecha UKASH
        $this->fecha = gmdate("Y-m-d H:i:s", time() + 7200); //Spain = GMT+1
        //EUR by default

        $this->sRequest = <<<XML
    <UKashTransaction>
        <ukashLogin>UKASH_Hipodromo</ukashLogin>
        <ukashPassword>2eZLGxWuP9NhQOIN</ukashPassword>
        <transactionId>$this->transaction_id</transactionId>
        <brandId>UKASH19450</brandId>
        <voucherNumber>$this->voucher_number</voucherNumber>
        <voucherValue>$this->voucher_value</voucherValue>
        <baseCurr>$this->currency</baseCurr>
        <ticketValue>$this->transaction_amount</ticketValue>
        <redemptionType>22</redemptionType>
        <merchDateTime>$this->fecha</merchDateTime>
        <merchCustomValue></merchCustomValue>
        <storeLocationId></storeLocationId>
        <amountReference></amountReference>
        <ukashNumber></ukashNumber>
        <ukashPin></ukashPin>
    </UKashTransaction>
XML;

        $params = array('sRequest' => $this->sRequest);

        $url='https://processing.ukash.com/gateway/Ukash.WSDL';

        $client = new nusoap_client($url, true);

        $result = $client -> call('TransactionEnquiry',$params);

        $transaction = new SimpleXMLElement($result['TransactionEnquiryResult']);

        $transaction=get_object_vars($transaction);

        return $transaction;


    }

}

