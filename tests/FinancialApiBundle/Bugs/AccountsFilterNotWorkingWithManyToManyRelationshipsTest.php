<?php

namespace Test\FinancialApiBundle\Bugs;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Symfony\Component\HttpFoundation\Response;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3ReadTestInterface;
use Test\FinancialApiBundle\CrudV3WriteTestInterface;

/**
 * Class AccountsFilterNotWorkingWithManyToManyRelationshipsTest
 * @package Test\FinancialApiBundle\Bugs
 */
class AccountsFilterNotWorkingWithManyToManyRelationshipsTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
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
