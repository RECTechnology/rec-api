<?php

namespace App\Tests\C2BQualifications;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

class BadgesTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
    }

    function testCreateBadgeFromUserShouldFail(){
        $data = array(
            'name' => $this->faker->name,
            'name_es' => $this->faker->name,
            'name_ca' => $this->faker->name,
            'description' => $this->faker->text,
            'description_es' => $this->faker->text,
            'description_ca' => $this->faker->text,
            'enabled' => true
        );

        $routeAdmin = '/admin/v3/badges';
        $resp = $this->requestJson('POST', $routeAdmin, $data);

        self::assertEquals(
            403,
            $resp->getStatusCode(),
            "route: $routeAdmin, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $routeManager = '/manager/v3/badges';
        $resp = $this->requestJson('POST', $routeManager, $data);
        self::assertEquals(
            403,
            $resp->getStatusCode(),
            "route: $routeManager, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $routeUser = '/user/v3/badges';
        $resp = $this->requestJson('POST', $routeUser, $data);
        self::assertEquals(
            403,
            $resp->getStatusCode(),
            "route: $routeUser, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

    function testListBadgesFromPublicShouldWork(){

        $this->signOut();
        $route = '/public/v3/badges';
        $resp = $this->requestJson('GET', $route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

}