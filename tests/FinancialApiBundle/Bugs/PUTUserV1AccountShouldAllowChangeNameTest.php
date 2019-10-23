<?php

namespace Test\FinancialApiBundle\Bugs;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3ReadTestInterface;
use Test\FinancialApiBundle\CrudV3WriteTestInterface;

/**
 * Class PUTUserV1AccountShouldAllowChangeNameTest
 * @package Test\FinancialApiBundle\Bugs
 */
class PUTUserV1AccountShouldAllowChangeNameTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
    }

    function test()
    {
        $route = '/user/v1/account';
        $resp = $this->requestJson('PUT', $route, ['name' => $this->faker->name]);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

}
