<?php


namespace Test\FinancialApiBundle\Bugs;


use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class Issue142Test
 * @package Test\FinancialApiBundle\Bugs
 * @see https://github.com/QbitArtifacts/rec-api/issues/142
 */
class Issue142Test extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testIssueIsSolved(){
        $route = "/admin/v3/tiers";
        $resp = $this->requestJson('POST', $route, ['code' => "test"]);
        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }



}