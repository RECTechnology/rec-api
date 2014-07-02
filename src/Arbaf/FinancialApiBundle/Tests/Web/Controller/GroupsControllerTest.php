<?php

namespace Arbaf\FinancialApiBundle\Tests\Web\Controller;

use Arbaf\FinancialApiBundle\Tests\Web\AbstractApiWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GroupsControllerTest extends AbstractApiWebTestCase
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

    public function testGetAllAndGetFirstIfAnyReturnsOk() {
        $groups = $this->getAllGroups();

        $firstGroup = $groups[0];

        $client = static::getTestClient('ROLE_API_SUPER_ADMIN');

        $client->request('GET', '/groups/'.$firstGroup->id);

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

    public function testCreatesTestAndDeletes() {

        #CREATING GROUP 'functionalTest' (OK)
        $client = static::getTestClient('ROLE_API_SUPER_ADMIN');
        $params = array('name' => 'functinoalTest');
        $client->request('POST', '/groups', $params);
        $this->assertEquals(
            Response::HTTP_CREATED,
            $client->getResponse()->getStatusCode(), $client->getResponse()
        );

        #CREATING Again GROUP 'functionalTest' (CONFLICT)
        $client = static::getTestClient('ROLE_API_SUPER_ADMIN');
        $params = array('name' => 'functinoalTest');
        $client->request('POST', '/groups', $params);
        $this->assertEquals(
            Response::HTTP_CONFLICT,
            $client->getResponse()->getStatusCode(), $client->getResponse()
        );


        $groups = $this->getAllGroups();

        foreach($groups as $group){
            if($group->name === 'functinoalTest'){

                #DELETING GROUP 'functionalTest' (OK)
                $client = static::getTestClient('ROLE_API_SUPER_ADMIN');
                $client->request('DELETE', '/groups/'.$group->id);
                $this->assertEquals(
                    Response::HTTP_NO_CONTENT,
                    $client->getResponse()->getStatusCode(), $client->getResponse()
                );
                return;
            }
        }
    }
}
