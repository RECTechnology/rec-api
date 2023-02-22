<?php

namespace App\Tests\User;

use App\DataFixtures\UserFixtures;
use App\Entity\Tier;
use App\Tests\BaseApiTest;

/**
 * Class CrudV3ReadTest
 * @package App\Tests\User
 */
class UserCreateAccountsTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
    }

    function testRegisterAccountLevelIsKYC1()
    {
        $resp = $this->rest(
            'POST',
            '/register/v1/commerce/mobile',
            [
                'name' => 'Test_user',
                'password' => 'password',
                'repassword' => 'password',
                'phone' => 678912345,
                'prefix' => 34,
                'pin' => 1111,
                'repin' => 1111,
                'dni' => '43207297A',
                'security_question' => 'quien',
                'security_answer' => 'tu'
            ]
        );
        self::assertTrue(isset($resp->company));
        self::assertEquals(Tier::KYC_LEVELS[1], $resp->company->level->code);

    }
    function testCreateNewAccountLevelIsKYC0()
    {
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);

        $resp = $this->rest(
            'POST',
            '/user/v1/new/account',
            [
                'account_name' => 'Test_account',
                'company_cif' => '43207297A',
                'company_email' => 'email@email.com',
                'company_phone' => 678912345,
                'company_prefix' => 34,
                'type' => 'PRIVATE',
                'subtype' => 'NORMAL',
                'security_answer' => 'tu'
            ]
        );
        self::assertTrue(isset($resp->company));
        self::assertEquals(Tier::KYC_LEVELS[0], $resp->company->level->code);
    }

    function testAddUserToAccountHavingAccountWithSameName()
    {
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);

        $account = $this->rest('GET', "/user/v3/groups/search?name=duplicated_name&type=PRIVATE");
        self::assertEquals(sizeof($account), 1);

        $resp = $this->rest(
            'POST',
            '/manager/v1/groups/'.$account[0]->id,
            [
                'role' => 'ROLE_ADMIN',
                'user_dni' => UserFixtures::TEST_SECOND_USER_CREDENTIALS['username']
            ],
            [],
            201
        );
    }

}
