<?php

namespace Test\FinancialApiBundle\Discourse;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

class DiscourseLogInTest extends BaseApiTest{
    function setUp(): void
    {
        parent::setUp();
    }

    function testLogIn()
    {
        $client = self::getOAuthClient();
        $credentials = UserFixture::TEST_REZERO_USER_2_CREDENTIALS;
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
            200
        );

        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $resp = $this->requestJson('GET', 'admin/v3/account_awards');
        $content = json_decode($resp->getContent(),true);
        self::assertArrayHasKey('data', $content);
        self::assertEquals(1, $content['data']['total']);
    }

}