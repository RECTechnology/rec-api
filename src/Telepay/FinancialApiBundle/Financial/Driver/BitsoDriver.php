<?php

namespace Telepay\FinancialApiBundle\Financial\Driver;

use TelepayApi\Core\ApiRequest;
use TelepayApi\Core\JsonRequester;

class BitsoDriver extends JsonRequester {

    static $baseUrl = "https://api.bitso.com/v2/";

    public function ticker($book) {
        return $this->send($this->buildRequest(
            'public/getticker',
            array('book' => $book),
            "GET"
        ));
    }

    private function buildRequest($func, $urlParams, $method, $params = array()){
        return new ApiRequest(
            static::$baseUrl,
            $func,
            $urlParams,
            $method,
            $params,
            array()
        );
    }

}