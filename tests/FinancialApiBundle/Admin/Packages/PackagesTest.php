<?php

namespace Test\FinancialApiBundle\Admin\Packages;

use App\FinancialApiBundle\DataFixture\AccountFixture;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\DependencyInjection\App\Commons\DiscourseApiManager;
use App\FinancialApiBundle\Entity\Group;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class PackagesTest
 * @package Test\FinancialApiBundle\Admin\Pakages
 */
class PackagesTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testListPackages(){

        $route = '/admin/v3/packages';
        $resp = $this->requestJson('GET', $route);
        $content = json_decode($resp->getContent(),true);
        self::assertArrayHasKey('data', $content);

    }

}
