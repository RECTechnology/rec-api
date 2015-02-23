<?php

namespace Telepay\FinancialApiBundle\DependencyInjection\Services\Libs;

class MultivaService{

    var $amount;
    var $currency;
    var $address;
    var $order_id;
    var $merchant;
    var $store;
    var $terminal;
    var $urlback;

    
    function __construct($comtotal, $comcurrency, $comaddress,$comorder_id,$commerchant,$comstore,$comterminal,$comurlback){
      
      $this->amount=$comtotal;
      $this->currency=$comcurrency;
      $this->address=$comaddress;
      $this->order_id=$comorder_id;
      $this->merchant=$commerchant;
      $this->store=$comstore;
      $this->terminal=$comterminal;
      $this->urlback=$comurlback;
      
    }

    public function request(){

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
            'comurlback'    =>  $this->urlback
        );

        return $response;

    }

  }


