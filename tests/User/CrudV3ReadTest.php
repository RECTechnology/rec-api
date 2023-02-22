<?php

namespace App\Tests\User;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;
use App\Tests\CrudV3ReadTestInterface;

/**
 * Class CrudV3ReadTest
 * @package App\Tests\User
 */
class CrudV3ReadTest extends BaseApiTest implements CrudV3ReadTestInterface {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
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
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
        $route = '/admin/v3/user/2';
        $resp = $this->request('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $resp1 = $this->request('DELETE', $route);
        $resp = $this->request('GET', $route);
        self::assertEquals(
            404,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

    function testDeleteUserShouldWork()
    {
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
        $route = '/user/v3/users/2';

        $this->requestJson('DELETE', $route);
        self::assertResponseIsSuccessful();
    }

    function testDeleteNotOwnedUserShouldFail()
    {
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
        $route = '/user/v3/users/1';

        $resp = $this->requestJson('DELETE', $route);

        self::assertEquals(
            403,
            $resp->getStatusCode()
        );
    }
}
