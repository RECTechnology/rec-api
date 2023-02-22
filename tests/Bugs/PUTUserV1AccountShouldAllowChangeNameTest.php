<?php

namespace App\Tests\Bugs;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class PUTUserV1AccountShouldAllowChangeNameTest
 * @package App\Tests\Bugs
 */
class PUTUserV1AccountShouldAllowChangeNameTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
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
