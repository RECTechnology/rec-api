<?php

namespace App\Tests\Open;

use App\Tests\BaseApiTest;

class InfoTest extends BaseApiTest {

    public function testInfoIsWellFormed(){
        $route = "/public/v1/info";
        $info = $this->rest('GET', $route);
        self::assertResponseIsSuccessful();

        self::assertIsObject($info);
        self::assertTrue(property_exists($info, 'name'));
        self::assertTrue(property_exists($info, 'license'));
        self::assertTrue(property_exists($info, 'description'));
        self::assertTrue(property_exists($info, 'version'));
    }
}