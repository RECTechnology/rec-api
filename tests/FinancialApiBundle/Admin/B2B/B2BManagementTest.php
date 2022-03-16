<?php

namespace Test\FinancialApiBundle\Admin\B2B;

use App\FinancialApiBundle\Controller\Management\User\DiscourseController;
use App\FinancialApiBundle\DataFixture\AccountFixture;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\DependencyInjection\App\Commons\DiscourseApiManager;
use App\FinancialApiBundle\Entity\Group;
use PHPUnit\Util\Json;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class B2BManagementTest
 * @package Test\FinancialApiBundle\Admin\B2B
 */
class B2BManagementTest extends BaseApiTest {

    public function testGrantAccessToDiscourse(): void
    {
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $accountName = AccountFixture::TEST_ACCOUNT_REZERO_1['name'];

        //get account
        $route = '/admin/v3/accounts?name='.$accountName;
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
        self::assertEquals(Group::ACCESS_STATE_PENDING, $account["rezero_b2b_access"]);

        //grant access to forum
        $route = '/admin/v3/accounts/'.$account['id'];
        $resp = $this->requestJson('PUT', $route, array("rezero_b2b_access" => Group::ACCESS_STATE_GRANTED));
        self::assertEquals(
            400,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $content = json_decode($resp->getContent(), true);
        self::assertEquals($content['message'],"Rezero Username is not set" );

        //Setting rezero username
        $username = "rezero_test_46";
        $resp = $this->requestJson('PUT', $route, array("rezero_b2b_username" => $username));
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        //now we can use mock and grant access again
        $this->useDiscourseMock();
        $resp = $this->requestJson('PUT', $route, array("rezero_b2b_access" => Group::ACCESS_STATE_GRANTED));
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(), true);
        $account = $content['data'];
        self::assertEquals(Group::ACCESS_STATE_GRANTED, $account["rezero_b2b_access"]);
        self::assertArrayHasKey("rezero_b2b_api_key", $account);
        self::assertArrayHasKey("rezero_b2b_user_id", $account);

    }

    private function useDiscourseMock(){
        $discMock = $this->createMock(DiscourseApiManager::class);
        $objReg = new \stdClass();
        $objReg->success = true;
        $objReg->active = true;
        $objReg->message = "Tu cuenta está activa y lista para usar";
        $objReg->user_id = 14;
        $discMock->method('register')->willReturn($objReg);
        $discMock->method('generateApiKeys')->willReturn("kjlhsdfljshflsdf");

        $this->override('net.app.commons.discourse.api_manager', $discMock);

    }


}
