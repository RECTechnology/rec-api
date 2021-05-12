<?php

namespace Test\FinancialApiBundle\User;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Tier;
use App\FinancialApiBundle\Entity\User;
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
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);

        // TODO change to the new endpoint
        //$route = "user/v4/users/security/sms-code/change-password";
        $route = "/password_recovery/v1/request";
        $response = $this->rest(
            'POST',
            $route,
            [
                'dni' => "01234567A",
                'phone' => 789789789,
                'prefix' => 34
            ],
            [],
            200
        );

        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->getRepository(User::class)->findOneBy(['dni' => "01234567A"]);
        $sms_code = $user->getRecoverPasswordToken();
        $resp = $this->rest(
            'POST',
            '/app/v4/recover-password',
            [
                'dni' => '01234567A',
                'prefix' => 34,
                'phone' => 789789789,
                'smscode' => $sms_code,
                'password' => "123456789",
                'repassword' => "123456789"
            ],
            [
                'Content-Type' => 'application/json',
                'Authorization' => $this->token
            ],
            204
        );
    }

}
