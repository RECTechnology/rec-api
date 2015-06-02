<?php

namespace Telepay\FinancialApiBundle\Financial\Driver;

use TelepayApi\Core\ApiRequest;
use TelepayApi\Core\JsonRequester;

class BittrexDriver extends JsonRequester {
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

    public function getOrderBook($market, $type = 'both', $depth = 20){
        return $this->send($this->requestBuilder->build(
            'public/getorderbook',
            array(
                'market' => $market,
                'type' => $type,
                'depth' => $depth
            )
        ));
    }

    public function getBalances() {
        return $this->send($this->requestBuilder->build(
            'account/getbalances'
        ));
    }

    public function getBalance($currency) {
        return $this->send($this->requestBuilder->build(
            'account/getbalance',
            array('currency' => $currency)
        ));
    }

    public function withdraw($currency, $quantity, $address) {
        return $this->send($this->requestBuilder->build(
            'account/withdraw',
            array(
                'currency' => $currency,
                'quantity' => $quantity,
                'address' => $address,
            )
        ));
    }

    public function buy($market, $quantity, $rate){
        return $this->send($this->requestBuilder->build(
            'market/buylimit',
            array(
                'market' => $market,
                'quantity' => $quantity,
                'rate' => $rate
            )
        ));
    }

    public function sell($market, $quantity, $rate){
        return $this->send($this->requestBuilder->build(
            'market/selllimit',
            array(
                'market' => $market,
                'quantity' => $quantity,
                'rate' => $rate
            )
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
    public function build($function, $params = array()){
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