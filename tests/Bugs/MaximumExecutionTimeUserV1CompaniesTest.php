<?php

namespace App\Tests\Bugs;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class MaximumExecutionTimeUserV1CompaniesTest
 * @package App\Tests\Bugs
 */
class MaximumExecutionTimeUserV1CompaniesTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
    }

    function testExecutionTimeShouldBe200AndLessThan30Seconds()
    {
        $route = '/user/v1/companies';
        $start = time();
        $resp = $this->requestJson('GET', $route);
        $end = time();
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        self::assertLessThan(
            30,
            $end - $start,
            "route: $route, status_code: {$resp->getStatusCode()}, time: " . ($end - $start)
        );
    }
}
