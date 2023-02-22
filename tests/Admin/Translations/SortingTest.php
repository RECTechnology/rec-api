<?php

namespace App\Tests\Admin\Translations;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class SortingTest
 * @package App\Tests\Admin\Translations
 */
class SortingTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
    }

    private function createProduct($lang, $name){
        $route = '/admin/v3/product_kinds';
        $resp = $this->requestJson(
            'POST',
            $route,
            ['name' => $name],
            ['HTTP_Content-Language' => $lang]
        );
        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        return json_decode($resp->getContent())->data;

    }

    private function updateProduct($id, $lang, $name){
        $route = '/admin/v3/product_kinds/' . $id;
        $resp = $this->requestJson(
            'PUT',
            $route,
            ['name' => $name],
            ['HTTP_Content-Language' => $lang]
        );
        return json_decode($resp->getContent())->data;
    }

    function testOrderByShouldWorkForEnAndEs() {
        $params = [
            [
                ['lang' => 'en', 'name' => 'aa'],
                ['lang' => 'es', 'name' => 'bb'],
            ],
            [
                ['lang' => 'en', 'name' => 'ab'],
                ['lang' => 'es', 'name' => 'ba'],
            ]
        ];
        foreach ($params as $param){
            $product = $this->createProduct($param[0]['lang'], $param[0]['name']);
            $this->updateProduct($product->id, $param[1]['lang'], $param[1]['name']);
        }

        $route = '/admin/v3/product_kinds?sort=name&order=asc';

        $lang = 'en';
        $resp = $this->requestJson(
            'GET',
            $route,
            null,
            ['HTTP_Accept-Language' => $lang]
        );
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $products = json_decode($resp->getContent())->data->elements;
        self::assertEquals(count($params), count($products));
        self::assertEquals($params[0][0]['name'], $products[0]->name);
        self::assertEquals($params[1][0]['name'], $products[1]->name);

        $lang = 'es';
        $resp = $this->requestJson(
            'GET',
            $route,
            null,
            ['HTTP_Accept-Language' => $lang]
        );
        $products = json_decode($resp->getContent())->data->elements;
        self::assertEquals(count($params), count($products));
        self::assertEquals($params[1][1]['name'], $products[0]->name);
        self::assertEquals($params[0][1]['name'], $products[1]->name);

    }

}
