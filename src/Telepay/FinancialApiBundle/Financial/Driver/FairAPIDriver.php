<?php

namespace Telepay\FinancialApiBundle\Financial\Driver;

use TelepayApi\Core\ApiRequest;
use TelepayApi\Core\JsonRequester;

class FairAPIDriver extends JsonRequester {
    private $requestBuilder;

    public function __construct($fairAPI_url, $fairAPI_key, $fairAPI_secret) {
        $this->requestBuilder = new FairAPIRequestBuilder($fairAPI_url, $fairAPI_key, $fairAPI_secret);
    }

    public function checkBalance($email, $method, $amount){
        return $this->send($this->requestBuilder->build(
            'api/transaction',
            array(
                'email' => $email,
                'method' => $method,
                'amount' => $amount
            )
        ));
    }
}

class FairAPIRequestBuilder {
    private $fairAPI_url, $fairAPI_key, $fairAPI_secret;
    public function __construct($fairAPI_url, $fairAPI_key, $fairAPI_secret) {
        $this->fairAPI_url = $fairAPI_url;
        $this->fairAPI_key = $fairAPI_key;
        $this->fairAPI_secret = $fairAPI_secret;
    }
    public function build($function, $params = array()){
        return new FairAPIRequest(
            $this->fairAPI_url,
            $this->fairAPI_key,
            $this->fairAPI_secret,
            $function,
            $params
        );
    }
}

class FairAPIRequest extends ApiRequest {

    private $fairAPI_key, $fairAPI_secret;

    public function __construct($fairAPI_url, $fairAPI_key, $fairAPI_secret, $function, $urlParams) {
        $this->fairAPI_key = $fairAPI_key;
        $this->fairAPI_secret = $fairAPI_secret;
        parent::__construct($fairAPI_url, $function, $urlParams, 'POST', array(), array());
    }


    public function getUrlParams(){
        return array_merge(parent::getUrlParams(), array('apikey' => $this->fairAPI_key, 'nonce' => time()));
    }

    public function getHeaders(){
        $stringToSign = $this->getBaseUrl() . '/' . $this->getFunction().'?'.http_build_query($this->getUrlParams());
        $signature = hash_hmac('sha512', $stringToSign, $this->fairAPI_secret);
        return array_merge(parent::getHeaders(), array(
            'apisign' => $signature
        ));
    }
}