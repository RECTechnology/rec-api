<?php

namespace Test\FinancialApiBundle\User;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Tier;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3ReadTestInterface;

/**
 * Class CrudV3ReadTest
 * @package Test\FinancialApiBundle\User
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
                'phone' => 67891234,
                'prefix' => 0034,
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
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);

        $resp = $this->rest(
            'POST',
            '/user/v1/new/account',
            [
                'account_name' => 'Test_account',
                'company_cif' => '43207297A',
                'company_email' => 'email@email.com',
                'company_phone' => 678912345,
                'company_prefix' => 0034,
                'type' => 'PRIVATE',
                'subtype' => 'NORMAL',
                'security_answer' => 'tu'
            ]
        );
        self::assertTrue(isset($resp->company));
        self::assertEquals(Tier::KYC_LEVELS[0], $resp->company->level->code);
    }

}
