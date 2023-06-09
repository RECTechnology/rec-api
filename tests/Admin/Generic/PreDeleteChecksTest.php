<?php

namespace App\Tests\Admin\Generic;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class TestPreDeleteChecks
 * @package App\Tests\Admin\Generic
 */
class PreDeleteChecksTest extends BaseApiTest {

    private $product;

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);

        $route = '/admin/v3/accounts';
        $resp = $this->requestJson('GET', $route);
        $account = json_decode($resp->getContent())->data->elements[0];

        $route = "/admin/v3/product_kinds";
        $resp = $this->requestJson('POST', $route, ["name" => "test"]);
        $this->product = json_decode($resp->getContent())->data;

        $route = "/admin/v3/accounts/{$account->id}/consuming_products";
        $resp = $this->requestJson('POST', $route, ["id" => $this->product->id]);
        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

    function testDeleteMustReturn412()
    {
        $route = "/admin/v3/product_kinds/{$this->product->id}";
        $resp = $this->requestJson('DELETE', $route);

        self::assertEquals(
            412,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

}
