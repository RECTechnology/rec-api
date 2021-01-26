<?php

namespace Test\FinancialApiBundle\User;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3ReadTestInterface;

/**
 * Class CrudV3ReadTest
 * @package Test\FinancialApiBundle\User
 */
class CrudV3ReadTest extends BaseApiTest implements CrudV3ReadTestInterface {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
    }

    function testIndex()
    {
        foreach (self::CRUD_V3_ROUTES as $route) {
            $resp = $this->requestJson('GET', '/user/v3/' . $route);
            self::assertEquals(
                200,
                $resp->getStatusCode(),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );
        }
    }

    function testExport()
    {
        foreach (self::CRUD_V3_ROUTES as $route) {
            $resp = $this->request('GET', '/user/v3/' . $route . '/export');
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
            $resp = $this->request('GET', '/user/v3/' . $route . '/search');
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
            $resp = $this->request('GET', '/user/v3/' . $route . '/9999999999');
            self::assertEquals(
                404,
                $resp->getStatusCode(),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );
        }
    }
    function testDeleteUser()
    {
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $route = '/manager/v3/user/2';
        $resp = $this->request('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $resp = $this->request('DELETE', $route);
        $resp = $this->request('GET', $route);
        self::assertEquals(
            404,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }
}
