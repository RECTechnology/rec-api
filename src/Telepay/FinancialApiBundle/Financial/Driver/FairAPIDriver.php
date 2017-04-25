<?php

namespace Telepay\FinancialApiBundle\Financial\Driver;

use ChipChapLL\BaseRequester;
use ChipChapLL\Core\ApiKey;
use ChipChapLL\Core\Credentials;
use TelepayApi\Core\JsonRequester;

class FairAPIDriver extends JsonRequester {
    private $fairAPI_url, $fairAPI_key, $fairAPI_secret;

    public function __construct($fairAPI_url, $fairAPI_key, $fairAPI_secret) {
        $this->fairAPI_url = $fairAPI_url;
        $this->fairAPI_key = $fairAPI_key;
        $this->fairAPI_secret = $fairAPI_secret;
    }

    public function checkBalance($email, $method, $amount){
        $concept =  array(
            'email' => $email,
            'method' => $method,
            'amount' => $amount
        );
        $functionUrl = $this->fairAPI_url.'/api/transaction';

        $transaction = new Signer(new ApiKey(
            $this->fairAPI_key,
            $this->fairAPI_secret
        ));

        $response = $transaction->transaction(
            $functionUrl,
            $concept,
            'POST'
        );
        return $response;
    }

    public function delete($id){
        $functionUrl = $this->fairAPI_url.'/api/transaction/' . $id;

        $transaction = new Signer(new ApiKey(
            $this->fairAPI_key,
            $this->fairAPI_secret
        ));

        $response = $transaction->transaction(
            $functionUrl,
            array(),
            'DELETE'
        );
        return $response;
    }

    public function active($id){
        $functionUrl = $this->fairAPI_url.'/api/transaction/' . $id;

        $transaction = new Signer(new ApiKey(
            $this->fairAPI_key,
            $this->fairAPI_secret
        ));

        $concept =  array(
            'active' => 1
        );

        $response = $transaction->transaction(
            $functionUrl,
            $concept,
            'PUT'
        );
        return $response;
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

    public function transaction($url, array $content, $type){
        return $this->call(
            $url,
            [],
            $type,
            $content,
            []
        );
    }
}