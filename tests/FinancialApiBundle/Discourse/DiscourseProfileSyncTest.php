<?php

namespace Test\FinancialApiBundle\Discourse;


use App\FinancialApiBundle\Controller\Management\User\DiscourseController;
use App\FinancialApiBundle\DataFixture\AccountFixture;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\DependencyInjection\App\Commons\DiscourseApiManager;
use App\FinancialApiBundle\Entity\Group;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Test\FinancialApiBundle\BaseApiTest;

class DiscourseProfileSyncTest extends BaseApiTest{
    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_REZERO_USER_2_CREDENTIALS);
    }

    function testBridgeSyncProfile(){
        $accountName = AccountFixture::TEST_ACCOUNT_REZERO_2['name'];
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

        $this->override('net.app.commons.discourse.api_manager', $discMock);
    }


    private function getUpdateUsernameMockeResponse(){
        return array (
            'id' => 40,
            'username' => 'changedusername',
        );
    }

}