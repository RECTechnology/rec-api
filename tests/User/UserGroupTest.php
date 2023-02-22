<?php

namespace App\Tests\User;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class UserGroupTest
 * @package App\Tests\User
 */
class UserGroupTest extends BaseApiTest
{
    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
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


    function testShowUserV1FromSuperShouldWork()
    {
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
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
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
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
