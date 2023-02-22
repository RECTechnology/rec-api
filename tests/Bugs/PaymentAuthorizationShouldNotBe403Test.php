<?php

namespace App\Tests\Bugs;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class PaymentAuthorizationShouldNotBe403Test
 * @package App\Tests\Bugs
 */
class PaymentAuthorizationShouldNotBe403Test extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
    }

    function test()
    {
        $route = '/methods/v1/out/'.$this->getCryptoMethod();
        $resp = $this->requestJson('POST', $route, []);

        self::assertNotEquals(
            403,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

}
