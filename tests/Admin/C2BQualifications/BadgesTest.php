<?php

namespace App\Tests\Admin\C2BQualifications;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

class BadgesTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
    }

    function testCreateBadgeFromSuperShouldWork(){
        $route = '/admin/v3/badges';

        $data = array(
            'name' => $this->faker->name,
            'name_es' => $this->faker->name,
            'name_ca' => $this->faker->name,
            'description' => $this->faker->text,
            'description_es' => $this->faker->text,
            'description_ca' => $this->faker->text,
            'enabled' => true
        );
        $resp = $this->requestJson('POST', $route, $data);

        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );


    }

    function testUpdateBadgeFromSuperShouldWork(){
        $route = '/admin/v3/badges/1';

        $newName = $this->faker->name;
        $data = array(
            'name' => $newName,
        );
        $resp = $this->requestJson('PUT', $route, $data);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent());

        self::assertEquals($newName, $content->data->name);
    }

}