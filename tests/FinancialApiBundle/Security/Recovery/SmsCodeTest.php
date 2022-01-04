<?php

namespace Test\FinancialApiBundle\Transactions;

use App\FinancialApiBundle\Controller\Google2FA;
use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\Utils\MongoDBTrait;

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
                'dni' => "01234567L",
                'phone' => 789789789,
                'prefix' => 34
            ],
            [],
            200
        );
    }
}
