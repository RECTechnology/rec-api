<?php


namespace App\Tests\Bugs;


use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class Issue145Test
 * @package App\Tests\Bugs
 * @see https://github.com/QbitArtifacts/rec-api/issues/145
 */
class Issue145Test extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
    }

    function testIssueIsSolved(){
        $route = "/user/v1/account";
        $resp = $this->requestJson('PUT', $route, ['locale' => "ca"]);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }
}