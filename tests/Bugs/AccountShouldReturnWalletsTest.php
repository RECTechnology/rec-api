<?php

namespace App\Tests\Bugs;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class AccountShouldReturnWalletsTest
 * @package App\Tests\Bugs
 */
class AccountShouldReturnWalletsTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
    }

    function testIndexUsersShouldReturn200AndHaveAccounts()
    {
        $route = '/admin/v3/accounts';
        $resp = $this->requestJson('GET', $route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $content = json_decode($resp->getContent())->data->elements;
        foreach ($content as $account){
            self::assertIsObject($account);
            self::assertTrue(property_exists($account, "wallets"));
        }

    }

}
