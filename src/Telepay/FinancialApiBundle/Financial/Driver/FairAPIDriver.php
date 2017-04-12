<?php

namespace Telepay\FinancialApiBundle\Financial\Driver;

use ChipChapLL\BaseRequester;
use ChipChapLL\Core\ApiKey;
use ChipChapLL\Core\Credentials;
use TelepayApi\Core\ApiRequest;
use TelepayApi\Core\JsonRequester;

class FairAPIDriver extends JsonRequester {
    private $requestBuilder;

    public function __construct($fairAPI_url, $fairAPI_key, $fairAPI_secret) {
        $this->requestBuilder = new FairAPIRequestBuilder($fairAPI_url, $fairAPI_key, $fairAPI_secret);
    }

    public function checkBalance($email, $method, $amount){
        return $this->sendRequest($this->requestBuilder->build(
            'api/transaction',
            array(
                'email' => $email,
                'method' => $method,
                'amount' => $amount
            )
        ));
    }

    public function sendRequest(FairAPIRequest $request){

        $functionUrl =
            $request->getBaseUrl().'/'
            .$request->getFunction();


        $access_secret = 'wlqDEET8uIr5RN00AMuuceI9LLKMTNLpzlETlX3djVg=';
        $access_key = 'edbeb673024f2d0e23752e2814ca1ac4c589f761';

        $transaction = new Signer(new ApiKey(
            $access_key,
            $access_secret
        ));

        $content = array(
            'email' =>  'pere@robotunion.org',
            'method'    =>  'btc-halcash_es',
            'amount'    =>  10000
        );

        $functionUrl = 'https://pre-faircoop.chip-chap.com/api/transaction';

        $response = $transaction->transaction(
            $functionUrl,
            $content
        );

        return $response;
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

    public function getKey(){
        return  $this->fairAPI_key;
    }

    public function getSecret(){
        return  $this->fairAPI_secret;
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

class Signer extends BaseRequester{


    private $keys;

    /**
     * Signer constructor.
     * @param ApiKey $keys
     */
    public function __construct(ApiKey $keys)
    {
        $this->keys = $keys;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return '';
    }

    /**
     * @return Credentials
     */
    public function getCredentials()
    {
        return $this->keys;
    }

    public function transaction($url, array $content){
        return $this->call(
            $url,
            [],
            'POST',
            $content,
            []
        );
    }
}