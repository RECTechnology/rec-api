<?php

namespace Test\FinancialApiBundle\Admin\B2B;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class ReportClientsAndProvidersTest
 * @package Test\FinancialApiBundle\Admin\B2B
 */
class ReportClientsAndProvidersTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testReportClientsAndProviders()
    {
        $limit = 1;
        $routeListAccounts = '/admin/v3/accounts?limit=' . $limit;
        $resp = $this->requestJson('GET', $routeListAccounts);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $routeListAccounts, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $jsonResp = json_decode($resp->getContent());
        self::assertEquals(
            $limit,
            count($jsonResp->data->elements),
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
        $resp = $this->request(
            'GET',
            $route,
            null,
            [
                'HTTP_ACCEPT' => 'text/html',
                'HTTP_Accept-Language' => 'es'
            ]
        );
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
        self::assertStringContainsStringIgnoringCase(
            "CLIENTES Y PROVEEDORES DE SUS PRODUCTOS",
            $resp->getContent()
        );
        $resp = $this->request(
            'GET',
            $route,
            null,
            [
                'HTTP_ACCEPT' => 'text/html',
                'HTTP_Accept-Language' => 'en'
            ]
        );
        self::assertStringContainsStringIgnoringCase(
            "CLIENTS AND PROVIDERS FOR YOUR PRODUCTS",
            $resp->getContent()
        );
    }
}
