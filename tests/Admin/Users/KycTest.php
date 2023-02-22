<?php

namespace App\Tests\Admin\Users;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class KycTest
 * @package App\Tests\Admin\Users
 */
class KycTest extends BaseApiTest {


    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
    }

    function testUpdateKyc()
    {
        $route = '/admin/v3/kyc/2';
        $params = [
            "street_name" => "Barraca"
        ];
        $resp = $this->requestJson('PUT', $route, $params);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(), true);
        self::assertArrayHasKey('data', $content);
        $data = $content['data'];

        self::assertEquals("Barraca", $data['street_name']);
    }

    function testUpdateKycs()
    {
        $route = '/admin/v3/kycs/2';
        $params = [
            "street_name" => "Barraca"
        ];
        $resp = $this->requestJson('PUT', $route, $params);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(), true);
        self::assertArrayHasKey('data', $content);
        $data = $content['data'];

        self::assertEquals("Barraca", $data['street_name']);
    }

}
