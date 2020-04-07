<?php

namespace Test\FinancialApiBundle\Admin\Generic;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3ReadTestInterface;

/**
 * Class CrudV3ReadAllObjectsTest
 * @package Test\FinancialApiBundle\Admin\Generic
 */
class CrudV3ReadAllObjectsTest extends BaseApiTest implements CrudV3ReadTestInterface {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testIndex()
    {
        foreach (self::CRUD_V3_ROUTES as $route) {
            $this->rest('GET', '/admin/v3/' . $route);
        }
    }

    function testExport()
    {
        foreach (self::CRUD_V3_ROUTES as $route) {
            $resp = $this->request('GET', '/admin/v3/' . $route . '/export');
            self::assertEquals(
                200,
                $resp->getStatusCode(),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );
        }
    }

    function testSearch()
    {
        foreach (self::CRUD_V3_ROUTES as $route) {
            $resp = $this->request('GET', '/admin/v3/' . $route . '/search');
            self::assertEquals(
                200,
                $resp->getStatusCode(),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );
        }
    }

    function testShow()
    {
        foreach (self::CRUD_V3_ROUTES as $route) {
            $resp = $this->request('GET', '/admin/v3/' . $route . '/9999999');
            self::assertEquals(
                404,
                $resp->getStatusCode(),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );
        }
    }

}
