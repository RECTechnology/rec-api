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
class UserLogInTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
    }

    function testLogIn()
    {
        $resp = $this->rest(
            'POST',
            'oauth/v3/token',
            [
                'grant_type' => "password",
                'client_id' => "5fpgrt2ykokc8k08k44kc8c8oggwowk8k0kkgsosk0w4co4wc8",
                'client_secret' => "1gm0p9c4ehj4wko0k4osgksc4w00g0wkk000ko08448kk80wko",
                'username' => "01234567A",
                'password' => "123456789",
                'version' => 120,
            ]
        );
        self::assertEquals(1, $resp[0]->is_my_account);
    }

}
