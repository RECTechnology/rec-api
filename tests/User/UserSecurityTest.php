<?php

namespace App\Tests\User;

use App\Controller\Google2FA;
use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Tests\BaseApiTest;

/**
 * Class UserCallsTest
 * @package App\Tests\User
 */
class UserSecurityTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
    }

    function testLogIn()
    {
        $client = self::getOAuthClient();
        $credentials = UserFixtures::TEST_USER_CREDENTIALS;
        $resp = $this->rest(
            'POST',
            'oauth/v3/token',
            [
                'grant_type' => "password",
                'client_id' => "1_".$client->getRandomId(),
                'client_secret' => $client->getSecret(),
                'username' => $credentials["username"],
                'password' => $credentials["password"],
                'version' => 300,
                'platform' => 'android'
            ],
            [],
            200
        );
    }


    function testLogInWhitespaceShouldFail()
    {
        $client = self::getOAuthClient();
        $credentials = UserFixtures::TEST_USER_CREDENTIALS;
        $resp = $this->rest(
            'POST',
            'oauth/v3/token',
            [
                'grant_type' => "password",
                'client_id' => "1_".$client->getRandomId(),
                'client_secret' => $client->getSecret(),
                'username' => $credentials["username"].' ',
                'password' => $credentials["password"],
                'version' => 300,
                'platform' => 'android'
            ],
            [],
            400
        );
    }

    //function testLogInWithoutPlatformShouldFail()
    //{
    //    $client = self::getOAuthClient();
    //    $credentials = UserFixtures::TEST_USER_CREDENTIALS;
    //    $resp = $this->rest(
    //        'POST',
    //        'oauth/v3/token',
    //        [
    //            'grant_type' => "password",
    //            'client_id' => "1_".$client->getRandomId(),
    //            'client_secret' => $client->getSecret(),
    //            'username' => $credentials["username"],
    //            'password' => $credentials["password"],
    //            'version' => 120,
    //        ],
    //        [],
    //        404
    //    );
    //}

    function testLogInWithOldVersionShouldFail()
    {
        $client = self::getOAuthClient();
        $credentials = UserFixtures::TEST_USER_CREDENTIALS;
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
                'platform' => 'android'
            ],
            [],
            404
        );
    }

    function testLogInBadUsernameShouldReturn400()
    {
        $client = self::getOAuthClient();
        $credentials = UserFixtures::TEST_USER_CREDENTIALS;
        $resp = $this->rest(
            'POST',
            'oauth/v3/token',
            [
                'grant_type' => "password",
                'client_id' => "1_".$client->getRandomId(),
                'client_secret' => $client->getSecret(),
                'username' => 'fake',
                'password' => $credentials["password"],
                'version' => 300,
                'platform' => 'android'
            ],
            [],
            400
        );
    }

    function testLogInBadPasswordShouldReturn400()
    {
        $client = self::getOAuthClient();
        $credentials = UserFixtures::TEST_USER_CREDENTIALS;
        $resp = $this->rest(
            'POST',
            'oauth/v3/token',
            [
                'grant_type' => "password",
                'client_id' => "1_".$client->getRandomId(),
                'client_secret' => $client->getSecret(),
                'username' => $credentials["username"],
                'password' => 'fake',
                'version' => 300,
                'platform' => 'android'
            ],
            [],
            400
        );
    }

    function testLogInUserLockedShouldFail()
    {
        $client = self::getOAuthClient();
        $credentials = UserFixtures::TEST_USER_LOCKED_CREDENTIALS;
        $resp = $this->rest(
            'POST',
            'oauth/v3/token',
            [
                'grant_type' => "password",
                'client_id' => "1_".$client->getRandomId(),
                'client_secret' => $client->getSecret(),
                'username' => $credentials["username"],
                'password' => $credentials["password"],
                'version' => 300,
                'platform' => 'android'
            ],
            [],
            403
        );

        self::assertEquals('user_locked',$resp->error);
        self::assertEquals('User locked to protect user security',$resp->error_description);
    }

    function testLogInUserPhoneNonValidatedShouldReturnPhoneError()
    {
        $client = self::getOAuthClient();
        $credentials = UserFixtures::TEST_USER_PHONE_NON_VALIDATED;
        $resp = $this->rest(
            'POST',
            'oauth/v3/token',
            [
                'grant_type' => "password",
                'client_id' => "1_".$client->getRandomId(),
                'client_secret' => $client->getSecret(),
                'username' => $credentials["username"],
                'password' => $credentials["password"],
                'version' => 300,
                'platform' => 'android'
            ],
            [],
            400
        );

        self::assertEquals('not_validated_phone',$resp->error);
        self::assertEquals('User without phone validated',$resp->error_description);

    }

    //TODO disabled to avoid errors in github tests
    function _testLogInAdminPanel()
    {
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        /** @var User $user */
        $user = $em->getRepository(User::class)->findOneBy(['name' => "ADMINUSER"]);

        /** @var UserGroup $userGroups */
        $userGroups = $user->getGroups();
        $userGroups[0]->setRoles(['ROLE_SUPER_ADMIN']);

        $user->setActiveGroup($userGroups[1]);
        $user->setRoles($user->getRoles());
        $em->flush();

        $otp = Google2FA::oath_totp($user->getTwoFactorCode());
        //Admin Get token from app
        $client = self::getOAuthClientAdminPanel();
        $credentials = UserFixtures::TEST_ADMIN_CREDENTIALS;
        $resp = $this->rest(
            'POST',
            'oauth/v3/token',
            [
                'grant_type' => "password",
                'client_id' => "2_".$client->getRandomId(),
                'client_secret' => $client->getSecret(),
                'username' => $credentials["username"],
                'password' => $credentials["password"],
                'pin' => $otp,
                'version' => 300,
                'platform' => 'rec-admin'
            ],
            [],
            200
        );

    }

    /**
     * This test check that if client format is not valid and username and password are valid, the response returns the
     * client error first
     */
    function testLogInBadFormatClientRightCredentials()
    {
        $client = self::getOAuthClient();
        $credentials = UserFixtures::TEST_USER_CREDENTIALS;
        $resp = $this->rest(
            'POST',
            'oauth/v3/token',
            [
                'grant_type' => "password",
                'client_id' => $client->getRandomId(),
                'client_secret' => $client->getSecret(),
                'username' => $credentials["username"],
                'password' => $credentials["password"],
                'version' => 300,
                'platform' => 'android'
            ],
            [],
            400
        );

        self::assertEquals('not_validated_client', $resp->error);
        self::assertEquals('The client format is not valid', $resp->error_description);
    }

    function testGetTokenClientCredentials()
    {
        $client = self::getOAuthClient();
        $resp = $this->rest(
            'POST',
            'oauth/v3/token',
            [
                'username' => "37468884K",
                'grant_type' => "client_credentials",
                'client_id' => "1_".$client->getRandomId(),
                'client_secret' => $client->getSecret()
            ],
            [],
            200
        );
    }

    function testRecovery()
    {
        $route = "user/v4/users/security/sms-code/change-password";
        //$route = "/password_recovery/v1/request"; // old endpoint
        $response = $this->rest(
            'POST',
            $route,
            [
                'dni' => UserFixtures::TEST_USER_CREDENTIALS['username'],
                'phone' => 789789789,
                'prefix' => 34
            ],
            [],
            200
        );

        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->getRepository(User::class)->findOneBy(['dni' => UserFixtures::TEST_USER_CREDENTIALS['username']]);
        $sms_code = $user->getLastSmscode();
        $this->unlockUser($sms_code);
        $this->recoverPasswordWrongDni($sms_code);
        $this->recoverPassword($sms_code);
        $this->changePassword($sms_code);
        $this->validatePhone($sms_code);
        $this->changePin($sms_code);

    }

    function validatePhone($sms_code)
    {
        $resp = $this->rest(
            'POST',
            '/app/v4/validate-phone',
            [
                'dni' => UserFixtures::TEST_USER_CREDENTIALS['username'],
                'prefix' => 34,
                'phone' => 789789789,
                'smscode' => $sms_code
            ],
            [
                'Content-Type' => 'application/json',
                'Authorization' => $this->token
            ],
            204
        );
    }

    function changePassword($sms_code)
    {
        $route = '/user/v4/users/security/change-password';
        $resp = $this->rest(
            'PUT',
            $route,
            [
                'old_password' => 'user_user1',
                'password' => 'user_new',
                'repassword' => 'user_new',
                'sms_code' => $sms_code
            ],
            [],
            200
        );

    }

    function changePin($sms_code)
    {
        $route = '/user/v4/users/security/change-pin';
        $resp = $this->rest(
            'PUT',
            $route,
            [
                'password' => 'user_new',
                'pin' => '1111',
                'repin' => '1111',
                'sms_code' => $sms_code
            ],
            [],
            200
        );

    }

    /**
     * @param $sms_code
     */
    private function recoverPassword($sms_code): void
    {
        $resp = $this->rest(
            'POST',
            '/app/v4/recover-password',
            [
                'dni' => UserFixtures::TEST_USER_CREDENTIALS['username'],
                'prefix' => 34,
                'phone' => 789789789,
                'smscode' => $sms_code,
                'password' => "user_user1",
                'repassword' => "user_user1"
            ],
            [
                'Content-Type' => 'application/json',
                'Authorization' => $this->token
            ],
            204
        );
    }

    /**
     * @param $sms_code
     */
    private function recoverPasswordWrongDni($sms_code): void
    {
        $resp = $this->rest(
            'POST',
            '/app/v4/recover-password',
            [
                'dni' => "37468884K",
                'prefix' => 34,
                'phone' => 789789789,
                'smscode' => $sms_code,
                'password' => "user_user1",
                'repassword' => "user_user1"
            ],
            [
                'Content-Type' => 'application/json',
                'Authorization' => $this->token
            ],
            404
        );
    }

    /**
     * @param $sms_code
     */
    function testRecoverPasswordRequest()
    {
        $resp = $this->rest(
            'POST',
            '/app/v4/sms-code/recover-password',
            [
                'dni' => UserFixtures::TEST_USER_CREDENTIALS['username'],
                'prefix' => 34,
                'phone' => 789789789
            ],
            [],
            200
        );

    }

    /**
     * @param $sms_code
     */
    function testRecoverPasswordRequestWrongDNI()
    {
        $resp = $this->rest(
            'POST',
            '/app/v4/sms-code/recover-password',
            [
                'dni' => UserFixtures::TEST_USER_CREDENTIALS['username'].' ',
                'prefix' => 34,
                'phone' => 789789789
            ],
            [],
            400
        );

    }

    /**
     * @param $sms_code
     */
    private function unlockUser($sms_code): void
    {
        $resp = $this->rest(
            'POST',
            '/app/v4/unlock-user',
            [
                'dni' => UserFixtures::TEST_USER_CREDENTIALS['username'],
                'prefix' => 34,
                'phone' => 789789789,
                'smscode' => $sms_code
            ],
            [
                'Content-Type' => 'application/json',
                'Authorization' => $this->token
            ],
            204
        );
    }

    function testLogInB2BAccessPendingShouldFail()
    {
        $client = self::getOAuthClient();
        $credentials = UserFixtures::TEST_USER_CREDENTIALS;
        $user = $this->getSignedInUser();


        $this->rest(
            'PUT',
            '/user/v1/activegroup',
            ['group_id' => $user->accounts[0]->id],
            [],
            200
        );
        $resp = $this->rest(
            'POST',
            'oauth/v3/token',
            [
                'grant_type' => "password",
                'client_id' => "1_".$client->getRandomId(),
                'client_secret' => $client->getSecret(),
                'username' => $credentials["username"],
                'password' => $credentials["password"],
                'version' => 300,
                'platform' => 'rezero-b2b-web'
            ],
            [],
            403
        );
    }

}
