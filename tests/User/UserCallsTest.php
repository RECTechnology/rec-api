<?php

namespace App\Tests\User;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class UserCallsTest
 * @package App\Tests\User
 */
class UserCallsTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
    }

    function testPhoneList()
    {
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);

        $users = $this->getAsAdmin("/admin/v3/user");
        $phone_list = [];
        $user_phone = '';
        foreach ($users as $user){
            if ($user->username == UserFixtures::TEST_USER_CREDENTIALS['username']){
                $user_phone = $user->phone;
            }else{
                array_push($phone_list, $user->phone);
            }
        }
        $resp = $this->rest(
            'POST',
            'user/v1/public_phone_list',
            [
                'phone_list' => $phone_list
            ]
        );
        self::assertEquals(1, $resp[0]->is_my_account);
    }

    /**
     * @param string $route
     */
    private function getAsAdmin(string $route)
    {
        $this->signOut();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
        $resp = $this->rest('GET', $route);
        $this->signOut();
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
        return $resp;

    }
}
