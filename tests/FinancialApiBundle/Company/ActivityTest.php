<?php

namespace Test\FinancialApiBundle\Company;

use App\FinancialApiBundle\Controller\Google2FA;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Tier;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserGroup;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3ReadTestInterface;

/**
 * @package Test\FinancialApiBundle\Company
 */
class ActivityTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
    }


    function testGetActivities(): void
    {
        $resp = $this->rest(
            'GET',
            '/public/v4/activities',
            [],
            [],
            200
        );

        self::assertObjectHasAttribute("id", $resp[0]);
        self::assertObjectHasAttribute("name", $resp[0]);
        self::assertObjectHasAttribute("name_es", $resp[0]);
        self::assertObjectHasAttribute("name_ca", $resp[0]);
    }

    function testGetActivities_v3(): void
    {
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $resp = $this->rest(
            'GET',
            'admin/v3/activities?parent=1',
            [],
            [],
            200
        );
        self::assertEquals(1, $resp[0]->parent->id);
    }

    function testSearchActivities(): void
    {
        $resp = $this->rest(
            'GET',
            '/public/v4/activities/search?parent_id=1',
            [],
            [],
            200
        );

        self::assertObjectHasAttribute("id", $resp[0]);
        self::assertObjectHasAttribute("name", $resp[0]);
        self::assertObjectHasAttribute("name_es", $resp[0]);
        self::assertObjectHasAttribute("name_ca", $resp[0]);
    }

    function testAdminSearchActivities(): void
    {
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $resp = $this->rest(
            'GET',
            '/admin/v4/activities/search?parent_id=1',
            [],
            [],
            200
        );

        self::assertObjectHasAttribute("id", $resp[0]);
        self::assertObjectHasAttribute("name", $resp[0]);
        self::assertObjectHasAttribute("name_es", $resp[0]);
        self::assertObjectHasAttribute("name_ca", $resp[0]);
    }

}
