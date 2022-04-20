<?php

namespace Test\FinancialApiBundle\Bugs;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class ChangeNameWithLangEsShouldNotReturn400Test
 * @package Test\FinancialApiBundle\Bugs
 */
class ChangeNameWithLangEsShouldNotReturn400Test extends BaseApiTest {

    private $route;
    private $product;

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $this->route = '/admin/v3/product_kinds';
        $lang = 'en'; //creating Product with default language
        $resp = $this->requestJson(
            'POST',
            $this->route,
            ['name' => 'test'],
            ['HTTP_Content-Language' => $lang]
        );
        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $this->route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $this->product = json_decode($resp->getContent())->data;
    }

    function testChangeNameWithLangEsShouldNotReturn400()
    {
        $route = $this->route . '/' . $this->product->id;
        $resp = $this->requestJson(
            'PUT',
            $route,
            ['name' => 'test2'],
            ['HTTP_Content-Language' => 'es']
        );
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }
}
