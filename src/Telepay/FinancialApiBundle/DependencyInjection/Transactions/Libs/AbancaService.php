<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs;


use Symfony\Component\HttpKernel\Exception\HttpException;

class AbancaService{

    private $amount;
    private $transaction_id;
    private $description;
    private $url_notification;
    private $url_ok;
    private $url_ko;
    private $url_tpvv;
    private $clave;
    private $name;
    private $code;
    private $currency;
    private $transaction_type;
    private $terminal;
    private $url_env;
    private $url_final;
    private $url_base;
    
    function __construct($url_tpvv, $clave, $name, $code, $currency, $transaction_type, $terminal, $url_env, $url_base){

        $this->url_tpvv = $url_tpvv;
        $this->clave = $clave;
        $this->name = $name;
        $this->code = $code;
        $this->currency = $currency;
        $this->transaction_type = $transaction_type;
        $this->terminal = $terminal;
        $this->url_env = $url_env;
        $this->url_base = $url_base;

    }

    public function request($amount,$transaction_id,$description,$url_ok,$url_ko,$url_final){

        $this->amount = $amount;
        $this->transaction_id = $transaction_id;
        $this->description = $description;
        $this->url_final = $url_final;
        $this->url_notification = $this->url_base.$this->url_env.$this->url_final;
        $this->url_ok = $url_ok;
        $this->url_ko = $url_ko;
        $message = $this->amount.$this->transaction_id.$this->code.$this->currency.$this->transaction_type.$this->url_notification.$this->clave;
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

    public function notification($params){

        // Compute hash to sign form data
        // $signature=sha1_hex($amount,$order,$code,$currency,$response,$clave);
        $message = $params[0].$params[2].$params[3].$params[1].$params[5].$this->clave;

        $signature = strtoupper(sha1($message));

        if($signature == $params[4]){
            if($params[5]<=99){
                $status = 1;

            }else{

                $status = 0;

            }
            return $status;
        }else{
            throw new HttpException(403,'Forbidden');
        }
    }

  }


