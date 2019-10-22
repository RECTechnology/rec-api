<?php

namespace Test\FinancialApiBundle\Admin\Generic;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Symfony\Component\HttpFoundation\Response;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class CRUDSubresourcesTest
 * @package Test\FinancialApiBundle\Admin\Generic
 */
class CRUDSubresourcesTest extends BaseApiTest {

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

    function testAddAndRemoveProducts()
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

        $route = "/user/v3/accounts/{$this->account->id}/producing_products";
        $resp = $this->requestJson(
            'GET',
            $route
        );
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $route = "/user/v3/accounts/{$this->account->id}/producing_products/{$this->product->id}";
        $resp = $this->requestJson(
            'DELETE',
            $route
        );
        self::assertEquals(
            204,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

    function tearDown(): void
    {
        $route = "/user/v3/product_kinds/{$this->product->id}";
        $resp = $this->requestJson('DELETE', $route);
        self::assertEquals(
            Response::HTTP_FORBIDDEN,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        parent::tearDown();
    }

}
