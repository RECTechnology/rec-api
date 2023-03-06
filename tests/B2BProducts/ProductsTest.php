<?php

namespace App\Tests\B2BProducts;

use App\DataFixtures\UserFixtures;
use App\Entity\Challenge;
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
            'name' => $this->faker->name,
            'description' => $this->faker->text,
        );
        $resp = $this->requestJson('POST', $route, $data);

        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );


    }
}