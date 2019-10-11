<?php

namespace Test\FinancialApiBundle\Bugs;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3ReadTestInterface;
use Test\FinancialApiBundle\CrudV3WriteTestInterface;

/**
 * Class PaymentAuthorizationShouldNotBe403
 * @package Test\FinancialApiBundle\Bugs
 */
class PaymentAuthorizationShouldNotBe403 extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
    }

    function test()
    {
        $route = '/methods/v1/out/rec';
        $resp = $this->requestJson('POST', $route, []);

        self::assertNotEquals(
            403,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

}
