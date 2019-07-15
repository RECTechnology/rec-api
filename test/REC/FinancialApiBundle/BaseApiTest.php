<?php

namespace REC\FinancialApiBundle\Test;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BaseApiTest extends WebTestCase {


    /**
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    protected function getPublicClient(){
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

    protected function getUserClient(){

    }

    protected function getManagerClient(){

    }

    protected function getAdminClient(){

    }

    protected function initializeDB(){
        //TODO: initialize fresh db for test
        // * Drop database
        // * Create database
        // * Create user admin, user, manager and its accounts
    }

    protected function setUp(){
    }

}
