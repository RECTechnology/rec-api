<?php

namespace Test\FinancialApiBundle\Admin\Generic;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3ReadTestInterface;
use Test\FinancialApiBundle\CrudV3WriteTestInterface;

/**
 * Class StatusTest
 * @package Test\FinancialApiBundle\Admin\Generic
 */
class StatusTest extends BaseApiTest {

    private $accounts;

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $route = '/admin/v3/accounts';
        $resp = $this->requestJson('GET', $route);
        $this->accounts = json_decode($resp->getContent())->data->elements;
    }

    function testSetStatusMustReturn412()
    {
        $route = "/admin/v3/mailings";
        $resp = $this->requestJson('POST', $route, ['status' => 'scheduled']);

        self::assertEquals(
            412,
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
