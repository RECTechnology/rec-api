<?php

namespace Test\FinancialApiBundle\Admin\Generic;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3ReadTestInterface;
use Test\FinancialApiBundle\CrudV3WriteTestInterface;

/**
 * Class DashboardTest
 * @package Test\FinancialApiBundle\Admin\Generic
 */
class DashboardTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testTotals()
    {
        $route = "/admin/v3/dashboard/total/private";
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $route = "/admin/v3/dashboard/total/company";
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $route = "/admin/v3/dashboard/total/balance";
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

    function testNeighbourhoods()
    {
        $route = "/admin/v3/neighbourhoods";
        for($i=0; $i<10; $i++) {
            $resp = $this->requestJson(
                'POST',
                $route,
                [
                    'name' => $this->faker->name,
                    'description' => $this->faker->text
                ]
            );
            self::assertEquals(
                201,
                $resp->getStatusCode(),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );
        }

        $route = "/admin/v3/dashboard/neighbourhoods";
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

    const INTERVALS = ['year', 'month', 'day'];
    function testTimeSeries()
    {
        foreach (self::INTERVALS as $interval){
            $route = "/admin/v3/dashboard/timeseries/registers/$interval";
            $resp = $this->requestJson('GET', $route);
            self::assertEquals(
                200,
                $resp->getStatusCode(),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );
        }
    }

}
