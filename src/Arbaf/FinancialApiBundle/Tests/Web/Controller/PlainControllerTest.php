<?php

namespace Arbaf\FinancialApiBundle\Tests\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;


class PlainControllerTest extends WebTestCase
{

    public function testIndexShouldBeSuccessful() {

        $client = static::createClient(array(), array(
            'HTTP_HOST' => 'api.arbafinternational.com'
        ));

        $client->request('GET', '/test/auth/ip');

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testIndexResponseShouldBeJSON() {

        $client = static::createClient(array(), array(
            'HTTP_HOST' => 'api.arbafinternational.com',
            'HTTP_ACCEPT' => 'application/json'
        ));

        $client->request('GET', '/test/auth/ip');

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

        $client->request('GET', '/test/auth/ip');


        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Content-Type',
                'text/xml; charset=UTF-8'
            )
        );
    }

    public function testIndexShouldContainTestPlain() {

        $client = static::createClient(array(), array(
            'HTTP_HOST' => 'api.arbafinternational.com',
            'HTTP_ACCEPT' => 'application/json'
        ));

        $client->request('GET', '/test/auth/ip');

        $objectResponse = json_decode($client->getResponse()->getContent());

        $this->assertEquals('plain', $objectResponse->test);
    }
}
