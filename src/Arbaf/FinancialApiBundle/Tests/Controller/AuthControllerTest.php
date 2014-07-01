<?php

namespace Arbaf\FinancialApiBundle\Tests\Controller;

use Arbaf\FinancialApiBundle\Tests\RequestBuilder\SignatureHeaderBuilder;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Tests\TestClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Util\SecureRandom;

class AuthControllerTest extends WebTestCase
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

        $client->request('GET', '/test/auth');

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

        $client->request('GET', '/test/auth');

        $this->assertEquals(
            Response::HTTP_OK,
            $client->getResponse()->getStatusCode()
        );

        $client->request('GET', '/test/auth');

        $this->assertEquals(
            Response::HTTP_FORBIDDEN,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testShouldReturnUnauthorized() {

        $client = static::createClient(array(), array(
            'HTTP_HOST' => 'api.arbafinternational.com'
        ));

        $client->request('GET', '/test/auth');

        $this->assertEquals(
            Response::HTTP_UNAUTHORIZED,
            $client->getResponse()->getStatusCode()
        );
    }


}
