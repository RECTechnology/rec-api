<?php

namespace Test\FinancialApiBundle\Admin\System;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class SystemTest
 * @package Test\FinancialApiBundle\Admin\System
 */
class SystemTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $this->store = $this->getSingleStore();
        $this->setClientIp($this->faker->ipv4);
    }

    function testGetLast50Transactions(){
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $route = '/system/v1/last50';
        $resp = $this->requestJson('GET',$route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(),true);

        foreach ($content['data'] as $tx){
            self::assertArrayHasKey('group_data', $tx);
        }
    }

    function testGetLoad(){
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $route = '/system/v1/load';
        $resp = $this->requestJson('GET',$route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

    function testGetCores(){
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $route = '/system/v1/cores';
        $resp = $this->requestJson('GET',$route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

    function testGetMem(){
        self::markTestIncomplete("falla el comando free del controller");
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $route = '/system/v1/mem';
        $resp = $this->requestJson('GET',$route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

    function testGetNet(){
        self::markTestIncomplete("ifstat not found");
        //ifstat not found
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $route = '/system/v1/net';
        $resp = $this->requestJson('GET',$route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

    private function generateTransaction(){
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $route = "/methods/v1/out/rec";
        $resp = $this->rest(
            'POST',
            $route,
            [
                'address' => $this->store->rec_address,
                'amount' => 1e8,
                'concept' => 'Testing concept',
                'pin' => UserFixture::TEST_USER_CREDENTIALS['pin']
            ],
            [],
            201
        );
    }

    private function getSingleStore(){
        $store = $this->rest('GET', '/admin/v3/accounts?type=COMPANY')[0];
        $this->signOut();
        return $store;
    }

}
