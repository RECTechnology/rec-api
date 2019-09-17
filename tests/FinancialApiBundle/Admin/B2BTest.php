<?php

namespace Test\FinancialApiBundle\Admin;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class B2BTest
 * @package Test\FinancialApiBundle\Admin
 */
class B2BTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testReportClientsAndProviders()
    {
        $routeListAccounts = '/admin/v3/accounts?limit=1';
        $resp = $this->requestJson('GET', $routeListAccounts);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $routeListAccounts, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $jsonResp = json_decode($resp->getContent());
        self::assertGreaterThan(
            0,
            $jsonResp->data->total,
            "route: $routeListAccounts, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $account = $jsonResp->data->elements[0]->id;

        $route = "/admin/v3/accounts/$account/report_clients_providers";
        $resp = $this->request('GET', $route, null, ['HTTP_ACCEPT' => 'application/pdf']);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        self::assertStringContainsStringIgnoringCase(
            "application/pdf",
            $resp->headers->get('Content-Type'),
            "route: $route, status_code: {$resp->getStatusCode()}, headers: {$resp->headers}"
        );
        $resp = $this->request('GET', $route, null, ['HTTP_ACCEPT' => 'text/html']);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        self::assertStringContainsStringIgnoringCase(
            "text/html",
            $resp->headers->get('Content-Type'),
            "route: $route, status_code: {$resp->getStatusCode()}, headers: {$resp->headers}"
        );
    }
}
