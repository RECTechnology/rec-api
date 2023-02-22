<?php

namespace App\Tests\Bugs;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class UserShouldReturnAccountsTest
 * @package App\Tests\Bugs
 */
class UserShouldReturnAccountsTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
    }

    function testIndexUsersShouldReturn200AndHaveAccounts()
    {
        $route = '/admin/v3/users';
        $resp = $this->requestJson('GET', $route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $content = json_decode($resp->getContent())->data->elements;
        foreach ($content as $user){
            self::assertIsObject($user);
            self::assertTrue(property_exists($user, 'accounts'));
        }

    }

}
