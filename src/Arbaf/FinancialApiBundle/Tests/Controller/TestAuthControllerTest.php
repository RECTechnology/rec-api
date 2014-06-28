<?php

namespace Arbaf\FinancialApiBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Util\SecureRandom;

class TestAuthControllerTest extends WebTestCase
{
    public function testIndexShouldBeForbidden() {

        $client = static::createClient(array(), array(
            'HTTP_HOST' => 'api.arbafinternational.com'
        ));

        $client->request('GET', '/test/auth');

        $this->assertEquals(
            Response::HTTP_FORBIDDEN,
            $client->getResponse()->getStatusCode()
        );
    }
/*
    public function testIndexShouldBeSuccessful() {

        $generator = new SecureRandom();
        $access_token = "376d4bbd92f888484215abe3a897b44d54c0fc1f";
        $access_secret = base64_decode("oWSXOmpswKebjfY2nqVYZcOnG6EGOVqEQVz8B8+VZhk=");
        $nonce = md5($generator->nextBytes(32));
        $timestamp = time();
        $algorithm = "SHA256";
        $signature = hash_hmac(
            $algorithm,
            $access_token.$nonce.$timestamp,
            $access_secret
        );
        $client = static::createClient(array(), array(
            'HTTP_HOST' => 'api.arbafinternational.com',
            'HTTP_X-API-AUTHENTICATION' => 'Signature '
                ."access-token=\"$access_token\", "
                ."nonce=\"$nonce\", "
                ."timestamp=\"$timestamp\", "
                ."algorithm=\"$algorithm\", "
                ."signature=\"$signature\""
        ));

        $client->request('GET', '/app_dev.php/test/auth');

        $this->assertTrue($client->getResponse()->isSuccessful());
    }
*/

}
