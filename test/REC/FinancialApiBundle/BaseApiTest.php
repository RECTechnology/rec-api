<?php

namespace REC\FinancialApiBundle\Test;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BaseApiTest extends WebTestCase {


    /**
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    protected function createApiClient(){
        $client = static::createClient();
        $client->setServerParameters(
            [
                'HTTP_Content-Type' => 'application/json',
                'HTTP_Accept' => 'application/json'
            ]
        );
        return $client;
    }

    public function testDummy(){
        static::assertTrue(true);
    }
}
