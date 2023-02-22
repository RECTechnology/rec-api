<?php

namespace App\Tests\Bugs;

use App\DataFixtures\UserFixtures;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\BaseApiTest;

/**
 * Class AccountsFilterNotWorkingWithManyToManyRelationshipsTest
 * @package App\Tests\Bugs
 */
class AccountsFilterNotWorkingWithManyToManyRelationshipsTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
    }

    function testFilterManyToManyShouldWork()
    {
        $route = '/admin/v3/product_kinds';
        $resp = $this->requestJson('POST', $route);
        self::assertEquals(
            Response::HTTP_CREATED,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $product = json_decode($resp->getContent())->data;

        $route = "/admin/v3/accounts?producing_products={$product->id}";
        $resp = $this->requestJson('GET', $route);

        self::assertEquals(
            Response::HTTP_OK,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

}
