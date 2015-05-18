<?php

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Providers;

use TelepayApi\Core\ApiRequest;
use TelepayApi\Core\JsonRequester;
use TelepayApi\Core\Request;

class Bittrex extends JsonRequester {
    private $requestBuilder;

    public function __construct($bittrex_url, $bittrex_key, $bittrex_secret) {
        $this->requestBuilder = new BittrexRequestBuilder($bittrex_url, $bittrex_key, $bittrex_secret);
    }

    public function ticker($market) {
        return $this->send($this->requestBuilder->build(
            'public/getticker',
            array('market' => $market)
        ));
    }
}

class BittrexRequestBuilder {
    private $bittrexUrl, $bittrexKey, $bittrexSecret;
    public function __construct($bittrex_url, $bittrex_key, $bittrex_secret) {
        $this->bittrexUrl = $bittrex_url;
        $this->bittrexKey = $bittrex_key;
        $this->bittrexSecret = $bittrex_secret;
    }
    public function build($function, $params){
        return new BittrexRequest(
            $this->bittrexUrl,
            $this->bittrexKey,
            $this->bittrexSecret,
            $function,
            $params
        );
    }
}

class BittrexRequest extends ApiRequest {

    private $bittrexKey, $bittrexSecret;

    public function __construct($bittrex_url, $bittrex_key, $bittrex_secret, $function, $urlParams) {
        $this->bittrexKey = $bittrex_key;
        $this->bittrexSecret = $bittrex_secret;
        parent::__construct($bittrex_url, $function, $urlParams, 'GET', array(), array());
    }


    public function getUrlParams(){
        return array_merge(parent::getUrlParams(), array('apikey' => $this->bittrexKey, 'nonce' => time()));
    }

    public function getHeaders(){
        $stringToSign = $this->getBaseUrl() . '/' . $this->getFunction().'?'.http_build_query($this->getUrlParams());
        $signature = hash_hmac('sha512', $stringToSign, $this->bittrexSecret);
        return array_merge(parent::getHeaders(), array(
            'apisign' => $signature
        ));
    }
}