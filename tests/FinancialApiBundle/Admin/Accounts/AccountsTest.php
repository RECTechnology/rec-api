<?php

namespace Test\FinancialApiBundle\Admin\Accounts;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

class AccountsTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
    }


    function testDeleteRelations()
    {

        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $route = '/admin/v3/accounts';
        $resp = $this->requestJson('GET', $route);
        $account = json_decode($resp->getContent())->data->elements[0];

        $route = "/admin/v3/product_kinds";
        $resp = $this->requestJson('POST', $route, ["name" => "test"]);
        $product = json_decode($resp->getContent())->data;

        $route = "/admin/v3/activity/1";
        $resp = $this->requestJson('GET', $route);
        $activity = json_decode($resp->getContent())->data;

        $this->deleteActivity($account, $activity);
        $this->deleteProducingProducts($account, $product);
        $this->deleteConsumingProducts($account, $product);

    }

    /**
     * @param $account
     * @param $product
     */
    private function deleteConsumingProducts($account, $product)
    {
        $route = "/admin/v3/accounts/{$account->id}/consuming_products";
        $resp = $this->requestJson('POST', $route, ["id" => $product->id]);
        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $route = "/admin/v3/accounts/{$account->id}";
        $resp = $this->requestJson('GET', $route);
        self::assertCount(1, json_decode($resp->getContent())->data->consuming_products);

        $route = "/admin/v3/accounts/{$account->id}/consuming_products/{$product->id}";
        $resp = $this->requestJson('DELETE', $route);
        self::assertEquals(
            204,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $route = "/admin/v3/accounts/{$account->id}";
        $resp = $this->requestJson('GET', $route);
        self::assertCount(0, json_decode($resp->getContent())->data->consuming_products);

    }

    /**
     * @param $account
     * @param $product
     */
    private function deleteProducingProducts($account, $product)
    {
        $route = "/admin/v3/accounts/{$account->id}/producing_products";
        $resp = $this->requestJson('POST', $route, ["id" => $product->id]);
        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $route = "/admin/v3/accounts/{$account->id}";
        $resp = $this->requestJson('GET', $route);
        self::assertCount(1, json_decode($resp->getContent())->data->producing_products);

        $route = "/admin/v3/accounts/{$account->id}/producing_products/{$product->id}";
        $resp = $this->requestJson('DELETE', $route);
        self::assertEquals(
            204,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $route = "/admin/v3/accounts/{$account->id}";
        $resp = $this->requestJson('GET', $route);
        self::assertCount(0, json_decode($resp->getContent())->data->producing_products);

    }

    /**
     * @param $account
     * @param $activity
     */
    private function deleteActivity($account, $activity)
    {
        $route = "/admin/v3/accounts/{$account->id}/activities";
        $resp = $this->requestJson('POST', $route, ["id" => $activity->id]);
        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $route = "/admin/v3/accounts/{$account->id}";
        $resp = $this->requestJson('GET', $route);
        self::assertCount(1, json_decode($resp->getContent())->data->activities);

        $route = "/admin/v3/accounts/{$account->id}/activities/{$activity->id}";
        $resp = $this->requestJson('DELETE', $route);
        self::assertEquals(
            204,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $route = "/admin/v3/accounts/{$account->id}";
        $resp = $this->requestJson('GET', $route);
        self::assertCount(0, json_decode($resp->getContent())->data->activities);
    }
}
