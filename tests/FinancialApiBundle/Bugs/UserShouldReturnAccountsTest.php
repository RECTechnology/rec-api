<?php

namespace Test\FinancialApiBundle\Bugs;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3ReadTestInterface;
use Test\FinancialApiBundle\CrudV3WriteTestInterface;

/**
 * Class UserShouldReturnAccountsTest
 * @package Test\FinancialApiBundle\Bugs
 */
class UserShouldReturnAccountsTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
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
            self::assertObjectHasAttribute(
                "accounts",
                $user,
                "route: $route, status_code: {$resp->getStatusCode()}"
            );
        }

    }

}
