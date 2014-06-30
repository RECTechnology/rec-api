<?php

namespace Arbaf\FinancialApiBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Tests\TestClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Util\SecureRandom;

class TestAuthControllerTest extends WebTestCase
{
    public function testIndexShouldBeSuccessful() {

        $generator = new SecureRandom();
        $access_key = "376d4bbd92f888484215abe3a897b44d54c0fc1f";
        $access_secret = base64_decode("oWSXOmpswKebjfY2nqVYZcOnG6EGOVqEQVz8B8+VZhk=");
        $nonce = md5($generator->nextBytes(32));
        $timestamp = time()+600;
        $algorithm = "SHA256";
        $stringToEncrypt = $access_key.$nonce.$timestamp;
        $signature = hash_hmac($algorithm, $stringToEncrypt, $access_secret);
        $signatureHeader = 'Signature '
            ."access-key=\"$access_key\", "
            ."nonce=\"$nonce\", "
            ."timestamp=\"$timestamp\", "
            ."algorithm=\"$algorithm\", "
            ."signature=\"$signature\"";

        $client = static::createClient(array(), array(
            'HTTP_HOST' => 'api.arbafinternational.com',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X-API-AUTHORIZATION' => $signatureHeader
        ));

        $client->request('GET', '/test/auth');

        $this->assertEquals(
            Response::HTTP_FORBIDDEN,
            $client->getResponse()->getStatusCode()
        );

    }


    public function testIndexCallOkAndReuseCall() {

        $generator = new SecureRandom();
        $access_key = "376d4bbd92f888484215abe3a897b44d54c0fc1f";
        $access_secret = base64_decode("oWSXOmpswKebjfY2nqVYZcOnG6EGOVqEQVz8B8+VZhk=");
        $nonce = md5($generator->nextBytes(32));
        $timestamp = time();
        $algorithm = "SHA256";
        $stringToEncrypt = $access_key.$nonce.$timestamp;
        $signature = hash_hmac($algorithm, $stringToEncrypt, $access_secret);
        $signatureHeader = 'Signature '
            ."access-key=\"$access_key\", "
            ."nonce=\"$nonce\", "
            ."timestamp=\"$timestamp\", "
            ."algorithm=\"$algorithm\", "
            ."signature=\"$signature\"";

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
