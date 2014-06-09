<?php

namespace Arbaf\FinancialApiBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class TestControllerTest extends WebTestCase
{

    public function testTestIfTestAreRunnigProperly() {
        $this->assertTrue(true);
    }

    public function testIndexShouldBeSuccessful() {

        $client = static::createClient(array(), array(
            'HTTP_HOST' => 'api.arbafinternational.com'
        ));

        $client->request('GET', '/test');

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testIndexResponseShouldBeJSON() {

        $client = static::createClient(array(), array(
            'HTTP_HOST' => 'api.arbafinternational.com',
            'HTTP_ACCEPT' => 'application/json'
        ));

        $client->request('GET', '/test');

        $this->assertNotNull(json_decode($client->getResponse()->getContent()));

        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            )
        );

    }

    public function testIndexResponseShouldReturnXMLContentTypeAndCharsetUtf8() {

        $client = static::createClient(array(), array(
            'HTTP_HOST' => 'api.arbafinternational.com',
            'HTTP_ACCEPT' => 'application/xml'
        ));

        $client->request('GET', '/test');


        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Content-Type',
                'text/xml; charset=UTF-8'
            )
        );
    }

    public function testIndexShouldContainTestTrue() {

        $client = static::createClient(array(), array(
            'HTTP_HOST' => 'api.arbafinternational.com',
            'HTTP_ACCEPT' => 'application/json'
        ));

        $client->request('GET', '/test');

        $objectResponse = json_decode($client->getResponse()->getContent());

        $this->assertTrue($objectResponse->test);
    }
}
