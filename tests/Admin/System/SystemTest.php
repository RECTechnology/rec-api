<?php

namespace App\Tests\Admin\System;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class SystemTest
 * @package App\Tests\Admin\System
 */
class SystemTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
        $this->store = $this->getSingleStore();
        $this->setClientIp($this->faker->ipv4);
    }

    function testGetLast50Transactions(){
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);

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
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);

        $route = '/system/v1/load';
        $resp = $this->requestJson('GET',$route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

    function testGetCores(){
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);

        $route = '/system/v1/cores';
        $this->requestJson('GET',$route);
        self::assertResponseIsSuccessful();

    }

    function testGetMem(){
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);

        $route = '/system/v1/mem';
        $this->requestJson('GET',$route);
        self::assertResponseIsSuccessful();

    }

    function testGetNet(){
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);

        $route = '/system/v1/net';
        $this->requestJson('GET',$route);
        self::assertResponseIsSuccessful();

    }

    private function generateTransaction(){
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
        $route = "/methods/v1/out/".$this->getCryptoMethod();
        $resp = $this->rest(
            'POST',
            $route,
            [
                'address' => $this->store->rec_address,
                'amount' => 1e8,
                'concept' => 'Testing concept',
                'pin' => UserFixtures::TEST_USER_CREDENTIALS['pin']
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
