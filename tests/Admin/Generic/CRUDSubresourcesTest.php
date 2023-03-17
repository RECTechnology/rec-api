<?php

namespace App\Tests\Admin\Generic;

use App\DataFixtures\UserFixtures;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\BaseApiTest;

/**
 * Class CRUDSubresourcesTest
 * @package App\Tests\Admin\Generic
 */
class CRUDSubresourcesTest extends BaseApiTest {

    private $account;
    private $product_id;

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
        $resp = $this->requestJson('GET', '/user/v1/account');
        $accounts = json_decode($resp->getContent())->data->accounts;
        $this->account = $accounts[0];

        $this->product_id = 1;
    }

    function testAddAndRemoveProducts()
    {
        $route = "/user/v3/accounts/{$this->account->id}/producing_products";
        $resp = $this->requestJson(
            'POST',
            $route,
            ['id' => $this->product_id]
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
        $products = json_decode($resp->getContent())->data->elements;
        self::assertGreaterThan(0, count($products), "Expected more than 0 products in account");

        $route = "/user/v3/accounts/{$this->account->id}/producing_products/{$this->product_id}";
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
        $route = "/user/v3/product_kinds/{$this->product_id}";
        $resp = $this->requestJson('DELETE', $route);
        self::assertEquals(
            Response::HTTP_FORBIDDEN,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        parent::tearDown();
    }

}
