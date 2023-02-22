<?php

namespace App\Tests\Discourse;


use App\DataFixtures\AccountFixtures;
use App\DataFixtures\UserFixtures;
use App\DependencyInjection\Commons\DiscourseApiManager;
use App\Tests\BaseApiTest;

class DiscourseProfileSyncTest extends BaseApiTest{
    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_REZERO_USER_2_CREDENTIALS);
    }

    function testBridgeSyncProfile(){
        $accountName = AccountFixtures::TEST_ACCOUNT_REZERO_2['name'];
        //get account
        $route = '/user/v3/accounts?name='.$accountName;
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(), true);
        self::assertArrayHasKey('data', $content);
        $data = $content['data'];
        self::assertArrayHasKey('elements', $data);
        self::assertGreaterThan(0, count($data['elements']));
        $account = $data['elements'][0];

        $this->useDiscourseUpdateUsernameMock();
        $route = '/user/v3/accounts/'.$account['id'];
        $resp = $this->requestJson('PUT', $route, array("rezero_b2b_username" => "changed_username"));
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }


    private function useDiscourseUpdateUsernameMock()
    {
        $discMock = $this->createMock(DiscourseApiManager::class);
        $response = $this->getUpdateUsernameMockeResponse();
        $discMock->method('updateUsername')->willReturn($response);

        $this->inject('net.app.commons.discourse.api_manager', $discMock);
    }


    private function getUpdateUsernameMockeResponse(){
        return array (
            'id' => 40,
            'username' => 'changedusername',
        );
    }

}