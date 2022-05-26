<?php

namespace Test\FinancialApiBundle\Admin\B2B;

use App\FinancialApiBundle\DataFixture\AccountFixture;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\DependencyInjection\App\Commons\DiscourseApiManager;
use App\FinancialApiBundle\Entity\Group;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class AwardsTest
 * @package Test\FinancialApiBundle\Admin\B2B
 */
class AwardsTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testListAwards(){

        $route = '/admin/v3/awards';
        $resp = $this->requestJson('GET', $route);
        $content = json_decode($resp->getContent(),true);
        self::assertArrayHasKey('data', $content);

    }

    function testListAwardsAccount(){

        $route = '/admin/v3/account_awards';
        $resp = $this->requestJson('GET', $route);
        $content = json_decode($resp->getContent(),true);
        self::assertArrayHasKey('data', $content);

    }

}
