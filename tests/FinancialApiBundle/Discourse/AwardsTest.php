<?php

namespace Test\FinancialApiBundle\Discourse;


use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\DependencyInjection\App\Commons\DiscourseApiManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Test\FinancialApiBundle\BaseApiTest;

class AwardsTest extends BaseApiTest{
    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_REZERO_USER_2_CREDENTIALS);
    }

    function testListAwards(){

        $route = '/user/v3/awards';
        $resp = $this->requestJson('GET', $route);
        $content = json_decode($resp->getContent(),true);
        self::assertArrayHasKey('data', $content);

    }

}