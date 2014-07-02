<?php

namespace Arbaf\FinancialApiBundle\Tests\Web\Controller;

use Arbaf\FinancialApiBundle\Tests\Web\AbstractApiWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GroupsControllerTest extends AbstractApiWebTestCase
{
    public function testGetAllReturnsOk() {
        $client = static::getTestClient('ROLE_API_SUPER_ADMIN');

        $client->request('GET', '/groups');

        $this->assertEquals(
            Response::HTTP_OK,
            $client->getResponse()->getStatusCode(), $client->getResponse()
        );
    }

    public function testAddGroupWithoutNameReturnsBadRequest() {
        $client = static::getTestClient('ROLE_API_SUPER_ADMIN');

        $client->request('POST', '/groups');

        $this->assertEquals(
            Response::HTTP_BAD_REQUEST,
            $client->getResponse()->getStatusCode(), $client->getResponse()
        );
    }

    public function testCreatesTestAndDeletesTest() {
        $client = static::getTestClient('ROLE_API_SUPER_ADMIN');

        $params = array(
            'name' => 'test'
        );

        $client->request('POST', '/groups', $params);

        $this->assertEquals(
            Response::HTTP_CREATED,
            $client->getResponse()->getStatusCode(), $client->getResponse()
        );

        $client = static::getTestClient('ROLE_API_SUPER_ADMIN');

        $client->request('GET', '/groups');

        $this->assertEquals(
            Response::HTTP_OK,
            $client->getResponse()->getStatusCode(), $client->getResponse()
        );

        $response = json_decode($client->getResponse()->getContent());

        $groups = $response->data->groups;

        foreach($groups as $group){
            if($group->name === 'test'){
                $client = static::getTestClient('ROLE_API_SUPER_ADMIN');

                $client->request('DELETE', '/groups/'.$group->id);

                $this->assertEquals(
                    Response::HTTP_NO_CONTENT,
                    $client->getResponse()->getStatusCode(), $client->getResponse()
                );
            }
        }
    }

}
