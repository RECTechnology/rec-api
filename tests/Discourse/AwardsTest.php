<?php

namespace App\Tests\Discourse;


use App\DataFixtures\UserFixtures;
use App\DependencyInjection\Commons\DiscourseApiManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Tests\BaseApiTest;

class AwardsTest extends BaseApiTest{
    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_REZERO_USER_2_CREDENTIALS);
    }

    function testListAwards(){

        $route = '/user/v3/awards';
        $resp = $this->requestJson('GET', $route);
        $content = json_decode($resp->getContent(),true);
        self::assertArrayHasKey('data', $content);

    }

}