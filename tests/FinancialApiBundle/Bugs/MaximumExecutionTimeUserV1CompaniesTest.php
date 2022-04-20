<?php

namespace Test\FinancialApiBundle\Bugs;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class MaximumExecutionTimeUserV1CompaniesTest
 * @package Test\FinancialApiBundle\Bugs
 */
class MaximumExecutionTimeUserV1CompaniesTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
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
