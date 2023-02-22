<?php

namespace App\Tests\User;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class UserAccountTest
 * @package App\Tests\User
 */
class UserAccountTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
    }

    function testUserReturnsRelevantInformation()
    {
        //I've used this user because is the only one that has setted the active group field
        //I've tried to add thjis field to the rest of the users but xDebug fails with an infinite loop, you can try
        // with third user for example and setActiveGroup in AccountFixtures

        $this->signIn(UserFixtures::TEST_SECOND_USER_CREDENTIALS);
        $resp = $this->requestJson(
            'GET',
            '/user/v1/account'
        );

        $content = json_decode($resp->getContent(),true);
        $data = $content['data'];
        self::assertArrayHasKey('bank_cards', $data);
        self::assertArrayHasKey('active_group', $data);
        self::assertArrayHasKey('is_kyc_manager', $data);
        self::assertEquals(false, $data['is_kyc_manager']);
        $accounts = $data['accounts'];
        self::assertArrayNotHasKey('commissions', $accounts[0]);
        self::assertArrayNotHasKey('cash_in_tokens', $accounts[0]);
        self::assertArrayNotHasKey('allowed_methods', $accounts[0]);
        self::assertArrayNotHasKey('limit_configuration', $accounts[0]);
        self::assertArrayNotHasKey('limit_counts', $accounts[0]);
    }

    function testUserAccountResumeShouldWork()
    {
        $this->signIn(UserFixtures::TEST_THIRD_USER_CREDENTIALS);
        $resp = $this->requestJson(
            'GET',
            '/user/v1/account/resume'
        );

        $content = json_decode($resp->getContent(),true);
        $data = $content['data'];
        self::assertArrayHasKey('total_purchases', $data);
        self::assertArrayHasKey('total_spent', $data);
        self::assertArrayHasKey('completed_challenges', $data);
    }

    function testUserAccountV3(){
        $this->signIn(UserFixtures::TEST_THIRD_USER_CREDENTIALS);
        $resp = $this->requestJson(
            'GET',
            '/user/v3/accounts'
        );

        self::assertEquals(200, $resp->getStatusCode());


    }

    function testUserGetOtherAccountV3(){
        $this->signIn(UserFixtures::TEST_THIRD_USER_CREDENTIALS);
        $resp = $this->requestJson(
            'GET',
            '/user/v3/accounts/22'
        );

        self::assertEquals(200, $resp->getStatusCode());

    }

    function testPublicGetOtherAccountV3(){
        $resp = $this->requestJson(
            'GET',
            '/public/v3/accounts/22'
        );

        self::assertEquals(200, $resp->getStatusCode());

        $content = json_decode($resp->getContent(),true);


        $data = $content['data'];
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('name', $data);
        self::assertArrayHasKey('rec_address', $data);
        self::assertArrayHasKey('prefix', $data);
        self::assertArrayHasKey('phone', $data);
        self::assertArrayHasKey('latitude', $data);
        self::assertArrayHasKey('longitude', $data);
        self::assertArrayHasKey('street', $data);
        self::assertArrayHasKey('type', $data);
        self::assertArrayHasKey('subtype', $data);
        self::assertArrayHasKey('public_image', $data);

    }
}
