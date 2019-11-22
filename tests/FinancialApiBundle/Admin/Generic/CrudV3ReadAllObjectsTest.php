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

    const ROUTES_TO_TEST = [
        'neighbourhoods',
        'activities',
        'product_kinds',
        'users',
        'accounts',
        'categories',
        'delegated_changes',
        'delegated_change_data',
        'treasure_withdrawals',
        'treasure_validations',
        'access_tokens',
        'clients',
        'cash_in_deposits',
        'user_wallets',
        'limit_counts',
        'limit_definitions',
        'mailings',
        'mailing_deliveries',
        'tiers',
        'document_kinds',
        'documents',
    ];

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testIndex()
    {
        foreach (self::CRUD_V3_ROUTES as $route) {
            $resp = $this->requestJson('GET', '/admin/v3/' . $route);
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
