<?php

namespace Arbaf\FinancialApiBundle\Tests\Web\Controller;

use Arbaf\FinancialApiBundle\Tests\Web\AbstractApiWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UsersControllerTest extends AbstractApiWebTestCase
{
    private function getAllUsers(){
        $client = static::getTestClient('ROLE_API_ADMIN');
        $client->request('GET', '/users');
        $this->assertEquals(
            Response::HTTP_OK,
            $client->getResponse()->getStatusCode(), $client->getResponse()
        );
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(
            count($response->data->users)>0
            ,'Number of users: '.count($response->data->users)
        );
        return $response->data->users;
    }

    public function testGetAllAndGetFirstIfAnyReturnsOk() {
        $users = $this->getAllUsers();

        $firstUser = $users[0];

        $client = static::getTestClient('ROLE_API_ADMIN');

        $client->request('GET', '/users/'.$firstUser->id);

        $this->assertEquals(
            Response::HTTP_OK,
            $client->getResponse()->getStatusCode(), $client->getResponse()
        );
    }
}
