<?php

namespace App\Tests\Admin\B2B;

use App\DataFixtures\AccountFixtures;
use App\DataFixtures\UserFixtures;
use App\DependencyInjection\Commons\DiscourseApiManager;
use App\Entity\Group;
use App\Tests\BaseApiTest;

/**
 * Class ProductsAdminTest
 * @package App\Tests\Admin\B2B
 */
class ProductsAdminTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
    }

    function testSearchProductActivity(){

        //search activity 1, culture
        $activity_id = 1;
        $params = "&sort=id&order=DESC&limit=15";
        $route = "/admin/v3/product_kind/search?activity=";
        $resp = $this->requestJson('GET', $route.$activity_id.$params);
        $content = json_decode($resp->getContent(),true);
        self::assertEquals(14, $content['data']['total']);
    }

    function testSearchProductOrder(){

        $route = "/admin/v3/product_kind/search?order=desc&limit=50&offset=0&sort=status";
        $resp = $this->requestJson('GET', $route);
        $content = json_decode($resp->getContent(),true);

        self::assertEquals(14, $content['data']['total']);
    }
}
