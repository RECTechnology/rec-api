<?php

namespace App\Tests\B2BProducts;

use App\DataFixtures\UserFixtures;
use App\Entity\Activity;
use App\Entity\ProductKind;
use App\Tests\BaseApiTest;

class ProductsTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
    }

    function testCreateProductShouldWork(){
        $route = '/user/v3/product_kinds';
        $data = array(
            'name' => 'banana',
            'type' => 'consuming'
        );
        $resp = $this->requestJson('POST', $route, $data);

        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

    public function testGetProductsShouldReturnNeededKeys(){
        $route = "/user/v3/product_kinds";

        $resp = $this->requestJson('GET', $route);

        $content = json_decode($resp->getContent(),true);

        $elements = $content['data']['elements'];

        foreach ($elements as $product){
            self::assertArrayHasKey('id', $product);
            self::assertArrayHasKey('name', $product);
            self::assertArrayHasKey('name_es', $product);
            self::assertArrayHasKey('name_ca', $product);
            self::assertArrayHasKey('description', $product);
            self::assertArrayHasKey('description_es', $product);
            self::assertArrayHasKey('description_ca', $product);
            self::assertEquals(ProductKind::STATUS_REVIEWED, $product['status']);
        }
    }

    function testSearchProductsByNameShouldWork(){
        $route = "/user/v3/product_kinds/search?search=";
        $word = '';
        $resp = $this->requestJson('GET', $route.$word);

        self::assertEquals(403, $resp->getStatusCode());

        $word = 'b';
        //this should return at least 1 -> banana
        $resp = $this->requestJson('GET', $route.$word);
        $content = json_decode($resp->getContent(),true);
        self::assertGreaterThanOrEqual(1, $content['data']['total']);
        foreach ($content['data']['elements'] as $product){
            self::assertEquals(ProductKind::STATUS_REVIEWED, $product['status']);
        }

        $word = 'ba';
        $resp = $this->requestJson('GET', $route.$word);
        $content = json_decode($resp->getContent(),true);
        self::assertGreaterThanOrEqual(1, $content['data']['total']);
    }

    function testSearchProductsExists(){
        $route = "/user/v3/product_kinds/exists";
        $name = 'Mussel';
        $resp = $this->requestJson('POST', $route, ["name" => $name]);
        $content = json_decode($resp->getContent(),true);

        self::assertGreaterThanOrEqual(1, $content['data']['total']);

        $name_es = 'Mejillón';
        $resp = $this->requestJson('POST', $route, ["name_es" => $name_es]);
        $content = json_decode($resp->getContent(),true);

        self::assertGreaterThanOrEqual(1, $content['data']['total']);

        $name_cat = 'Musclo';
        $resp = $this->requestJson('POST', $route, ["name_cat" => $name_cat]);
        $content = json_decode($resp->getContent(),true);

        self::assertGreaterThanOrEqual(1, $content['data']['total']);
    }

    function testUserSendReportClientsAndProviders()
    {
        $resp = $this->requestJson("GET", "/user/v1/account");

        $me = json_decode($resp->getContent())->data;
        $route = "/user/v3/accounts/{$me->accounts[0]->id}/mailing_report_clients_providers";
        $resp = $this->request('GET', $route);
        self::assertResponseIsSuccessful();
    }


}