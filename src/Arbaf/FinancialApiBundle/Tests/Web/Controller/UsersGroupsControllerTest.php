<?php

namespace Arbaf\FinancialApiBundle\Tests\Web\Controller;

use Arbaf\FinancialApiBundle\Tests\Web\AbstractApiWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UsersGroupsControllerTest extends AbstractApiWebTestCase
{

    private function getAllUsers(){
        $client = static::getTestClient('ROLE_API_ADMIN');
        $client->request('GET', '/admin/users');
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

    private function getAllGroups(){
        $client = static::getTestClient('ROLE_API_SUPER_ADMIN');
        $client->request('GET', '/admin/groups');
        $this->assertEquals(
            Response::HTTP_OK,
            $client->getResponse()->getStatusCode(), $client->getResponse()
        );
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(
            count($response->data->groups)>0
            ,'Number of groups: '.count($response->data->groups)
        );
        return $response->data->groups;
    }




    public function testReadUserAddAndDeleteGroupTest(){

        $firstUser = $this->getAllUsers()[0];
        $firstGroup = $this->getAllGroups()[0];

        #ADDING ROLE to first user (OK)
        $client = static::getTestClient('ROLE_API_ADMIN');
        $params = array('group_id' => $firstGroup->id);
        $client->request('POST', '/admin/users/'.$firstUser->id.'/groups', $params);
        $this->assertEquals(
            Response::HTTP_CONFLICT,
            $client->getResponse()->getStatusCode(), $client->getResponse()
        );

        #ADDING ROLE to first user (CONFLICT)
        $client = static::getTestClient('ROLE_API_ADMIN');
        $params = array('group_id' => $firstGroup->id);
        $client->request('POST', '/admin/users/'.$firstUser->id.'/groups', $params);
        $this->assertEquals(
            Response::HTTP_CONFLICT,
            $client->getResponse()->getStatusCode(), $client->getResponse()
        );
/*
        #DELETE ROLE to first user (OK)
        $client = static::getTestClient('ROLE_API_ADMIN');
        $client->request('DELETE', '/admin/users/'.$firstUser->id.'/groups/'.$firstGroup->id);
        $this->assertEquals(
            Response::HTTP_NO_CONTENT,
            $client->getResponse()->getStatusCode(), $client->getResponse()
        );
*/

    }


}
