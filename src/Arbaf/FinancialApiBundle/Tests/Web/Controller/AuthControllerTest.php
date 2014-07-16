<?php

namespace Arbaf\FinancialApiBundle\Tests\Controller;

use Arbaf\FinancialApiBundle\DependencyInjection\SignatureHeaderBuilder;
use Arbaf\FinancialApiBundle\Tests\Web\AbstractApiWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends AbstractApiWebTestCase
{
    public function testIndexShouldBeSuccessful() {

        $signatureHeader = SignatureHeaderBuilder::build(
            "edbeb673024f2d0e23752e2814ca1ac4c589f761",
            "wlqDEET8uIr5RN00AMuuceI9LLKMTNLpzlETlX3djVg="
        );

        $client = static::createClient(array(), array(
            'HTTP_HOST' => 'api.arbafinternational.com',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X-API-AUTHORIZATION' => $signatureHeader
        ));

        $client->request('GET', '/test/auth/signature');

        $this->assertEquals(
            Response::HTTP_OK,
            $client->getResponse()->getStatusCode(), $client->getResponse()
        );

    }


    public function testIndexCallOkAndReuseCall() {
        $signatureHeader = SignatureHeaderBuilder::build(
            "edbeb673024f2d0e23752e2814ca1ac4c589f761",
            "wlqDEET8uIr5RN00AMuuceI9LLKMTNLpzlETlX3djVg="
        );

        $client = static::createClient(array(), array(
            'HTTP_HOST' => 'api.arbafinternational.com',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X-API-AUTHORIZATION' => $signatureHeader
        ));

        $client->request('GET', '/test/auth/signature');

        $this->assertEquals(
            Response::HTTP_OK,
            $client->getResponse()->getStatusCode()
        );

        $client->request('GET', '/test/auth/signature');

        $this->assertEquals(
            Response::HTTP_FORBIDDEN,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testShouldReturnUnauthorized() {

        $client = static::createClient(array(), array(
            'HTTP_HOST' => 'api.arbafinternational.com'
        ));

        $client->request('GET', '/test/auth/signature');

        $this->assertEquals(
            Response::HTTP_UNAUTHORIZED,
            $client->getResponse()->getStatusCode()
        );
    }


}
