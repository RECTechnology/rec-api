<?php

namespace Arbaf\FinancialApiBundle\Tests\Web\Controller;

use Arbaf\FinancialApiBundle\Tests\Web\AbstractApiWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GroupsRolesControllerTest extends AbstractApiWebTestCase
{

    private function getAllGroups(){
        $client = static::getTestClient('ROLE_API_SUPER_ADMIN');
        $client->request('GET', '/groups');
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

    public function testReadGroupAddAndDeleteRoleTest(){

        $groups = $this->getAllGroups();
        $firstGroup = $groups[0];

        #ADDING ROLE to first group (OK)
        $client = static::getTestClient('ROLE_API_SUPER_ADMIN');
        $params = array('role_name' => 'ROLE_API_SERVICES_TEST');
        $client->request('POST', '/groups/'.$firstGroup->id.'/roles', $params);
        $this->assertEquals(
            Response::HTTP_CREATED,
            $client->getResponse()->getStatusCode(), $client->getResponse()
        );

        #ADDING ROLE to first group (CONFLICT)
        $client = static::getTestClient('ROLE_API_SUPER_ADMIN');
        $params = array('role_name' => 'ROLE_API_SERVICES_TEST');
        $client->request('POST', '/groups/'.$firstGroup->id.'/roles', $params);
        $this->assertEquals(
            Response::HTTP_CONFLICT,
            $client->getResponse()->getStatusCode(), $client->getResponse()
        );

        #ADDING ROLE to first group (CONFLICT)
        $client = static::getTestClient('ROLE_API_SUPER_ADMIN');
        $client->request('DELETE', '/groups/'.$firstGroup->id.'/roles/ROLE_API_SERVICES_TEST');
        $this->assertEquals(
            Response::HTTP_NO_CONTENT,
            $client->getResponse()->getStatusCode(), $client->getResponse()
        );


    }


}
