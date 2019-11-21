<?php

namespace Test\FinancialApiBundle\Admin\B2B;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class ReportClientsAndProvidersTest
 * @package Test\FinancialApiBundle\Admin\B2B
 */
class ReportClientsAndProvidersTest extends BaseApiTest {

    const REPORT_HEADER = [
        'es' => 'CLIENTES Y PROVEEDORES DE TUS PRODUCTOS',
        'en' => 'CLIENTS AND PROVIDERS FOR YOUR PRODUCTS',
    ];

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testReportClientsAndProviders()
    {
        $routeListAccounts = '/admin/v3/accounts';
        $resp = $this->requestJson('GET', $routeListAccounts);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $routeListAccounts, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $accounts = json_decode($resp->getContent())->data->elements;

        foreach ($accounts as $account){

            $route = "/admin/v3/accounts/{$account->id}/report_clients_providers";
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

            $this->dump('report.pdf', $resp->getContent());

            $resp = $this->request(
                'GET',
                $route,
                null,
                [
                    'HTTP_ACCEPT' => 'text/html',
                    'HTTP_Accept-Language' => $account->kyc_manager->locale
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
                self::REPORT_HEADER[$account->kyc_manager->locale],
                $resp->getContent()
            );
        }
    }
}
