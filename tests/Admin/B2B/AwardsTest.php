<?php

namespace App\Tests\Admin\B2B;

use App\DataFixtures\AccountFixtures;
use App\DataFixtures\UserFixtures;
use App\DependencyInjection\Commons\DiscourseApiManager;
use App\Entity\Group;
use App\Tests\BaseApiTest;

/**
 * Class AwardsTest
 * @package App\Tests\Admin\B2B
 */
class AwardsTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
    }

    function testListAwards(){

        $route = '/admin/v3/awards';
        $resp = $this->requestJson('GET', $route);
        $content = json_decode($resp->getContent(),true);
        $this->assertResponseIsSuccessful();
        self::assertArrayHasKey('data', $content);

    }

    function testListAwardsAccount(){

        $route = '/admin/v3/account_awards';
        $resp = $this->requestJson('GET', $route);
        $content = json_decode($resp->getContent(),true);
        $this->assertResponseIsSuccessful();
        self::assertArrayHasKey('data', $content);

    }

}
