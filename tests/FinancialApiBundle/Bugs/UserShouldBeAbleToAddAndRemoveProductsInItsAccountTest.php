<?php

namespace Test\FinancialApiBundle\Bugs;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3ReadTestInterface;
use Test\FinancialApiBundle\CrudV3WriteTestInterface;

/**
 * Class UserShouldBeAbleToAddAndRemoveProductsInItsAccountTest
 * @package Test\FinancialApiBundle\Bugs
 */
class UserShouldBeAbleToAddAndRemoveProductsInItsAccountTest extends BaseApiTest {

    private $account;
    private $product;

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $resp = $this->requestJson('GET', '/user/v1/account');
        $accounts = json_decode($resp->getContent())->data->accounts;
        $this->account = $accounts[0];

        $resp = $this->requestJson('POST', '/user/v3/product_kinds');
        $this->product = json_decode($resp->getContent())->data;
    }

    function test()
    {
        $route = "/user/v3/accounts/{$this->account->id}/producing_products";
        $resp = $this->requestJson(
            'POST',
            $route,
            ['id' => $this->product->id]
        );
        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

}
