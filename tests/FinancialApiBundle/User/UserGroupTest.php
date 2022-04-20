<?php

namespace Test\FinancialApiBundle\User;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class UserGroupTest
 * @package Test\FinancialApiBundle\User
 */
class UserGroupTest extends BaseApiTest
{
    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
    }

    function testListUsersByGroup()
    {
        $user = $this->getSignedInUser();
        $this->rest(
            'GET',
            '/users/v1/usersbygroup/'.$user->group_data->id,
            [],
            [],
            200
        );
    }

    function testListUsersByGroupInNonOwnedAccountShouldFail()
    {
        $this->rest(
            'GET',
            '/users/v1/usersbygroup/5',
            [],
            [],
            403
        );
    }

    function testListUsersByGroupV2()
    {
        //este endpoint falla con este error
        //Internal Server Error,"message":"[Semantical Error] line 0, col 15 near 'id) FROM App\\FinancialApiBundle\\Entity\\UserGroup': Error: Class App\\FinancialApiBundle\\Entity\\UserGroup has no field or association named id
        self::markTestIncomplete();
        $user = $this->getSignedInUser();
        $this->rest(
            'GET',
            '/manager/v2/usersbygroup/'.$user->group_data->id,
            [],
            [],
            200
        );
    }

    function testListUsersV1FromSuperShouldWork()
    {
        $this->markTestIncomplete("Takes too long to complete");
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $this->rest(
            'GET',
            '/manager/v1/users',
            [],
            [],
            200
        );
    }

    function testListUsersV1FromUserShouldFail()
    {
        $this->rest(
            'GET',
            '/manager/v1/users',
            [],
            [],
            403
        );
    }

    function testShowUserV1FromSuperShouldWork()
    {
        $this->markTestIncomplete("Takes too long to complete");
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $this->rest(
            'GET',
            '/manager/v1/users/3',
            [],
            [],
            200
        );
    }

    function testShowUserV1FromUserShouldWork()
    {
        $this->markTestIncomplete("Takes too long to complete");
        $user = $this->getSignedInUser();
        $this->rest(
            'GET',
            '/manager/v1/users/'.$user->group_data->id,
            [],
            [],
            200
        );
    }

    function testListUsersV2FromSuperShouldWork()
    {
        $this->markTestIncomplete("Takes too long to complete");
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $resp = $this->rest(
            'GET',
            '/manager/v2/users',
            [],
            [],
            200
        );
    }

    function testListUsersV2FromUserShouldFail()
    {
        $resp = $this->rest(
            'GET',
            '/manager/v2/users',
            [],
            [],
            403
        );
    }

}
