<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Services\Libs;


class SabadellService{

    var $amount;
      var $transaction_id;
      var $description;
      var $url_notification;
      var $url_ok;
      var $url_ko;
      var $url_tpvv;
      var $clave;
      var $name;
      var $code;
      var $currency;
      var $transaction_type;
      var $terminal;

    
    function __construct($amount,$transaction_id,$description,$url_notification,$url_ok,$url_ko,$url_tpvv,$clave,$name,$code,$currency,$transaction_type,$terminal){
      
        $this->amount=$amount;
        $this->transaction_id=$transaction_id;
        $this->description=$description;
        $this->url_notification=$url_notification;
        $this->url_ok=$url_ok;
        $this->url_ko=$url_ko;
        $this->url_tpvv=$url_tpvv;
        $this->clave=$clave;
        $this->name=$name;
        $this->code=$code;
        $this->currency=$currency;
        $this->transaction_type=$transaction_type;
        $this->terminal=$terminal;
      
    }

    public function request(){

        $message=$this->amount.$this->transaction_id.$this->code.$this->currency.$this->transaction_type.$this->url_notification.$this->clave;
        $signature=strtoupper(sha1($message));

        $response=array(
            'Ds_Merchant_Amount'        =>  $this->amount,
            'Ds_Merchant_Currency'      =>  $this->currency,
            'Ds_Merchant_Order'         =>  $this->transaction_id,
            'Ds_Merchant_MerchantCode'  =>  $this->code,
            'Ds_Merchant_Terminal'      =>  $this->terminal,
            'Ds_Merchant_TransactionType'=>  $this->transaction_type,
            'Ds_Merchant_MerchantURL'   =>  $this->url_notification,
            'Ds_Merchant_UrlOK'         =>  $this->url_ok,
            'Ds_Merchant_UrlKO'         =>  $this->url_ko,
            'Ds_Merchant_Signature'     =>  $signature,
            'Ds_Merchant_TpvV'          =>  $this->url_tpvv

        );

        return $response;

    }

  }


