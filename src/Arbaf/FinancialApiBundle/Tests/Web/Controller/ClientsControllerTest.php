<?php

namespace Arbaf\FinancialApiBundle\Tests\Web\Controller;

use Arbaf\FinancialApiBundle\DependencyInjection\SignatureHeaderBuilder;
use Arbaf\FinancialApiBundle\Tests\Web\AbstractApiWebTestCase;
use Symfony\Component\BrowserKit\Tests\TestClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Util\SecureRandom;

class ClientsControllerTest extends AbstractApiWebTestCase
{
    public function testGetAllClients() {
        $signatureHeader = SignatureHeaderBuilder::build(
            "edbeb673024f2d0e23752e2814ca1ac4c589f761",//SUPER_ADMIN
            "wlqDEET8uIr5RN00AMuuceI9LLKMTNLpzlETlX3djVg="
        );
        $client = static::createClient(array(), array(
            'HTTP_HOST' => 'api.arbafinternational.com',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X-API-AUTHORIZATION' => $signatureHeader
        ));

        $client->request('GET', '/clients');

        $this->assertEquals(
            Response::HTTP_OK,
            $client->getResponse()->getStatusCode(), $client->getRequest()
        );
    }

    public function testGetAllUsersShouldContainOneAdmin() {

        $signatureHeader = SignatureHeaderBuilder::build(
            "edbeb673024f2d0e23752e2814ca1ac4c589f761",//SUPER_ADMIN
            "wlqDEET8uIr5RN00AMuuceI9LLKMTNLpzlETlX3djVg="
        );

        $client = static::createClient(array(), array(
            'HTTP_HOST' => 'api.arbafinternational.com',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X-API-AUTHORIZATION' => $signatureHeader
        ));

        $client->request('GET', '/clients');

        $this->assertEquals(
            Response::HTTP_OK,
            $client->getResponse()->getStatusCode(), $client->getResponse()
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, count($response->data->clients), $client->getResponse());

    }

    public function testGetAllUsersShouldContainOneUser() {

        $signatureHeader = SignatureHeaderBuilder::build(
            "edbeb673024f2d0e23752e2814ca1ac4c589f761",//SUPER_ADMIN
            "wlqDEET8uIr5RN00AMuuceI9LLKMTNLpzlETlX3djVg="
        );

        $client = static::createClient(array(), array(
            'HTTP_HOST' => 'api.arbafinternational.com',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X-API-AUTHORIZATION' => $signatureHeader
        ));

        $client->request('GET', '/users');

        $this->assertEquals(
            Response::HTTP_OK,
            $client->getResponse()->getStatusCode(), $client->getResponse()
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, count($response->data->users), $client->getResponse());

    }

    public function testGetAllUsersFromAdminShouldBeForbidden() {
        $signatureHeader = SignatureHeaderBuilder::build(
            "8eb4763b5feda5a966c6c5749231f45841631f28", //ADMIN
            "hqTXol8N2fT7Rx2whVElnV5zbyzoRC8M+EX4G7JJdRA="
        );

        $client = static::createClient(array(), array(
            'HTTP_HOST' => 'api.arbafinternational.com',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X-API-AUTHORIZATION' => $signatureHeader
        ));

        $client->request('GET', '/clients');

        $this->assertEquals(
            Response::HTTP_FORBIDDEN,
            $client->getResponse()->getStatusCode(), $client->getResponse()
        );
    }
}
