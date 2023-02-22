<?php

namespace App\Tests\Security\Recovery;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class TransactionsTest
 * @package App\Tests\Transactions
 */
class SmsCodeTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->setClientIp($this->faker->ipv4);

    }

    function testSendSmsCodeShouldReturn200(){
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
        $route = "user/v4/users/security/sms-code/change-password";
        $response = $this->rest(
            'POST',
            $route,
            [
                'dni' => "01234567l",
                'phone' => 789789789,
                'prefix' => 34
            ],
            [],
            200
        );
    }
}
