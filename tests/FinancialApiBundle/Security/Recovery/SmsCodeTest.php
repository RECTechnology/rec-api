<?php

namespace Test\FinancialApiBundle\Security\Recovery;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class TransactionsTest
 * @package Test\FinancialApiBundle\Transactions
 */
class SmsCodeTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->setClientIp($this->faker->ipv4);

    }

    function testSendSmsCodeShouldReturn200(){
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
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
