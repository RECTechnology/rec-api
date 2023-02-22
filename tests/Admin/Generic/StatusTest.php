<?php

namespace App\Tests\Admin\Generic;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class StatusTest
 * @package App\Tests\Admin\Generic
 */
class StatusTest extends BaseApiTest {

    private $accounts;

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);

        $route = '/admin/v3/accounts';
        $resp = $this->requestJson('GET', $route);
        $this->accounts = json_decode($resp->getContent())->data->elements;
    }

    function testSetStatusMustReturn412()
    {
        $route = "/admin/v3/mailings";
        $resp = $this->requestJson('POST', $route, ['status' => 'scheduled']);

        self::assertEquals(
            400,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

    function testChangeToNonAllowedStatusMustReturn400()
    {
        $route = "/admin/v3/mailings";
        $resp = $this->requestJson('POST', $route);
        $mailing = json_decode($resp->getContent())->data;
        $route = "/admin/v3/mailings/{$mailing->id}";
        $resp = $this->requestJson('PUT', $route, ['status' => 'capat']);
        self::assertEquals(
            400,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

    function testNotAllowedStatusChangeMustReturn412()
    {
        $route = "/admin/v3/mailings";
        $resp = $this->requestJson('POST', $route);
        $mailing = json_decode($resp->getContent())->data;
        $route = "/admin/v3/mailings/{$mailing->id}";
        $resp = $this->requestJson('PUT', $route, ['status' => 'processed']);
        self::assertEquals(
            400,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }


}
