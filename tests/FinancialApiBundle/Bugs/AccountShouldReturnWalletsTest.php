<?php

namespace Test\FinancialApiBundle\Bugs;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class AccountShouldReturnWalletsTest
 * @package Test\FinancialApiBundle\Bugs
 */
class AccountShouldReturnWalletsTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
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
            self::assertObjectHasAttribute(
                "wallets",
                $account,
                "route: $route, status_code: {$resp->getStatusCode()}"
            );
        }

    }

}
