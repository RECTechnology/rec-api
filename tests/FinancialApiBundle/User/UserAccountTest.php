<?php

namespace Test\FinancialApiBundle\User;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class UserAccountTest
 * @package Test\FinancialApiBundle\User
 */
class UserAccountTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
    }

    function testUserReturnsBankCards()
    {
        $this->signIn(UserFixture::TEST_THIRD_USER_CREDENTIALS);
        $resp = $this->requestJson(
            'GET',
            '/user/v1/account'
        );

        $content = json_decode($resp->getContent(),true);
        $data = $content['data'];
        self::assertArrayHasKey('bank_cards', $data);
    }

    function testUserAccountResumeShouldWork()
    {
        $this->signIn(UserFixture::TEST_THIRD_USER_CREDENTIALS);
        $resp = $this->requestJson(
            'GET',
            '/user/v1/account/resume'
        );

        $content = json_decode($resp->getContent(),true);
        $data = $content['data'];
        self::assertArrayHasKey('total_purchases', $data);
        self::assertArrayHasKey('total_spent', $data);
        self::assertArrayHasKey('completed_challenges', $data);
    }
}
