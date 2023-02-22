<?php

namespace App\Tests\Admin\B2B;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;
use App\Tests\CrudV3WriteTestInterface;

/**
 * Class WriteNamedObjectsTest
 * @package App\Tests\Admin\B2B
 */
class WriteNamedObjectsTest extends BaseApiTest implements CrudV3WriteTestInterface {

    const ROUTES_TO_TEST = [
        'neighbourhoods',
        'activities',
        'product_kinds'
    ];

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
    }

    private function createNamedObjectAsAdmin($path, $name){
        $route = '/admin/v3/' . $path;
        $resp = $this->requestJson(
            'POST',
            $route,
            ['name' => $name]
        );
        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        return $resp;
    }

    function testCreate()
    {
        foreach (self::ROUTES_TO_TEST as $route) {
            $name = 'test object';
            $resp = $this->createNamedObjectAsAdmin($route, $name);
            self::assertEquals($name, json_decode($resp->getContent())->data->name);
        }
    }


    function testUpdate()
    {
        foreach (self::ROUTES_TO_TEST as $route) {
            $resp = $this->createNamedObjectAsAdmin($route, "initial name");
            $nhId = json_decode($resp->getContent())->data->id;

            $name = "changed name";
            $resp = $this->requestJson(
                'PUT',
                '/admin/v3/' . $route . '/' . $nhId,
                ['name' => $name]
            );
            self::assertEquals(
                200,
                $resp->getStatusCode(),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );
            self::assertEquals($name, json_decode($resp->getContent())->data->name);
        }
    }

    function testDelete()
    {
        foreach (self::ROUTES_TO_TEST as $route) {
            $resp = $this->createNamedObjectAsAdmin($route, "test name");
            $nhId = json_decode($resp->getContent())->data->id;

            $this->rest('DELETE', '/admin/v3/' . $route . '/' . $nhId);
        }
    }
}
