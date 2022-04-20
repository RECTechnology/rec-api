<?php

namespace Test\FinancialApiBundle\Company;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

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
        foreach ($resp as $activity){
            self::assertEquals(1, $activity->parent->id);
        }

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

        foreach ($resp as $activity){
            if(isset($activity->parent)){
                self::assertEquals(1, $activity->parent);
            }else{
                self::assertEquals(1, $activity->id);
            }
        }
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
        self::assertCount(3, $resp);
        self::assertObjectHasAttribute("id", $resp[0]);
        self::assertObjectHasAttribute("name", $resp[0]);
        self::assertObjectHasAttribute("name_es", $resp[0]);
        self::assertObjectHasAttribute("name_ca", $resp[0]);
        foreach ($resp as $activity){
            if(isset($activity->parent)){
                self::assertEquals(1, $activity->parent);
            }else{
                self::assertEquals(1, $activity->id);
            }
        }
    }

    function testAdminSearchActivitiesByNameCat(): void
    {
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $resp = $this->rest(
            'GET',
            '/admin/v4/activities/search?search=CatCult',
            [],
            [],
            200
        );

        self::assertCount(1, $resp);
        self::assertEquals("Culture", $resp[0]->name);
    }

    function testAdminSearchActivitiesByParentIdAndName(): void
    {
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $resp = $this->rest(
            'GET',
            '/admin/v4/activities/search?parent_id=null&search=Cu',
            [],
            [],
            200
        );

        self::assertCount(1, $resp);
        self::assertEquals("Culture", $resp[0]->name);
    }


}
