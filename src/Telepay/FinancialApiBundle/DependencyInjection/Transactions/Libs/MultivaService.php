<?php

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs;

class MultivaService{

    private $amount;
    private $currency;
    private $address;
    private $order_id;
    private $merchant;
    private $store;
    private $terminal;
    private $base_url;

    
    function __construct($comcurrency, $comaddress, $commerchant, $comstore, $comterminal, $base_url){

        $this->currency = $comcurrency;
        $this->address = $comaddress;
        $this->merchant = $commerchant;
        $this->store = $comstore;
        $this->terminal = $comterminal;
        $this->base_url = $base_url;

    }

    public function request($comtotal,  $comorder_id, $url_final){

        $this->amount=$comtotal;
        $this->order_id=$comorder_id;
        $this->url_final=$url_final;

        $url_notification=$this->base_url.$url_final;

        $digest=sha1($this->merchant.$this->store.$this->terminal.$this->amount.$this->currency.$this->order_id);

        $url='https://www.procom.prosa.com.mx/eMerchant/7531853_telepay.jsp';

        $response=array(
            'comtotal'      =>  $this->amount,
            'comcurrency'   =>  $this->currency,
            'comaddress'    =>  $this->address,
            'comorder_id'   =>  $this->order_id,
            'commerchant'   =>  $this->merchant,
            'comstore'      =>  $this->store,
            'comterm'       =>  $this->terminal,
            'comdigest'     =>  $digest,
            'comaction'     =>  $url,
            'comurlback'    =>  $url_notification
        );

        return $response;

    }

  }


