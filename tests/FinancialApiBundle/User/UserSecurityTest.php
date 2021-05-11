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
class UserSecurityTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
    }

    function testLogIn()
    {
        $client = self::getOAuthClient();
        $credentials = UserFixture::TEST_USER_CREDENTIALS;
        $resp = $this->rest(
            'POST',
            'oauth/v3/token',
            [
                'grant_type' => "password",
                'client_id' => "1_".$client->getRandomId(),
                'client_secret' => $client->getSecret(),
                'username' => $credentials["username"],
                'password' => $credentials["password"],
                'version' => 120,
            ],
            [],
            200
        );
    }

    function testPasswordRecovery()
    {
        $resp = $this->rest(
            'POST',
            '/app/v4/recover-password',
            [
                'code' => 123456,
                'password' => "123456789",
                'repassword' => "123456789"
            ],
            [
                'Authorization' => "Bearer OWI2MDg4OTMzOWYwYTFlMzkyMTYwODNkYTcxNGNlYzY4OWY0MWI4YjIzMjNiMzk0OGUxNmI3OTZlNjEzZWY2Ng"
            ]
        );
        self::assertEquals(1, $resp[0]->is_my_account);
    }

}
