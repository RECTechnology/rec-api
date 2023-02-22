<?php

namespace App\Tests\Company;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * @package App\Tests\Company
 */
class ActivityTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
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
        self::assertIsObject($resp[0]);
        self::assertTrue(property_exists($resp[0], 'id'));
        self::assertTrue(property_exists($resp[0], 'name'));
        self::assertTrue(property_exists($resp[0], 'name_es'));
        self::assertTrue(property_exists($resp[0], 'name_ca'));
    }

    function testGetActivities_v3(): void
    {
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
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

        self::assertIsObject($resp[0]);
        self::assertTrue(property_exists($resp[0], 'id'));
        self::assertTrue(property_exists($resp[0], 'name'));
        self::assertTrue(property_exists($resp[0], 'name_es'));
        self::assertTrue(property_exists($resp[0], 'name_ca'));

        foreach ($resp as $activity){
            if(isset($activity->parent_id)){
                self::assertEquals(1, $activity->parent_id);
            }else{
                self::assertEquals(1, $activity->id);
            }
        }
    }

    function testAdminSearchActivities(): void
    {
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
        $resp = $this->rest(
            'GET',
            '/admin/v4/activities/search?parent_id=1&sort=id&order=desc',
            [],
            [],
            200
        );
        self::assertCount(3, $resp);

        self::assertIsObject($resp[0]);
        self::assertTrue(property_exists($resp[0], 'id'));
        self::assertTrue(property_exists($resp[0], 'name'));
        self::assertTrue(property_exists($resp[0], 'name_es'));
        self::assertTrue(property_exists($resp[0], 'name_ca'));
        self::assertGreaterThan(1, $resp[0]->id);
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
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
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
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
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

    function testAdminSearchActivitiesWithLimit(): void
    {
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
        $resp = $this->rest(
            'GET',
            '/admin/v4/activities/search?limit=3&search=',
            [],
            [],
            200
        );

        self::assertCount(3, $resp);
    }


}
