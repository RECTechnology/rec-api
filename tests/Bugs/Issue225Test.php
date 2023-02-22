<?php


namespace App\Tests\Bugs;


use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class Issue225Test
 * @package App\Tests\Bugs
 * @see https://github.com/QbitArtifacts/rec-api/issues/225
 */
class Issue225Test extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
    }

    function testIssueIsSolved(){
        $route = "/user/v1/account";
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }
}