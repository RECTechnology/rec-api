<?php

namespace Test\FinancialApiBundle\User;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Tier;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3ReadTestInterface;

/**
 * Class UserCallsTest
 * @package Test\FinancialApiBundle\User
 */
class UserCallsTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
    }

    function testPhoneList()
    {
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);

        $users = $this->getAsAdmin("/admin/v3/user");
        $phone_list = [];
        $user_phone = '';
        foreach ($users as $user){
            if ($user->username == UserFixture::TEST_USER_CREDENTIALS['username']){
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
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $resp = $this->rest('GET', $route);
        $this->signOut();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        return $resp;

    }
}
