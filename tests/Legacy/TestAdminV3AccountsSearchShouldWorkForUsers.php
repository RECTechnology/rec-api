<?php

namespace App\Tests\Legacy;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class TestUserShouldReturnAccounts
 * @package App\Tests\Bugs
 */
class TestUserShouldReturnAccounts extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
    }

    function test()
    {
        $route = '/admin/v3/accounts/search';
        $resp = $this->requestJson('GET', $route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

}
