<?php

namespace Test\FinancialApiBundle\C2BChallenges;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

class AccountChallengesTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
    }

    function testCreateAccountChallengeFromUserShouldFail(){
        $route = '/user/v3/account_challenges';
        $user = $this->getSignedInUser();

        $data = array(
            'account_id' => $user->group_data->id,
            'challenge_id' => 1
        );
        $resp = $this->requestJson('POST', $route, $data);

        self::assertEquals(
            403,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

    function testListAccountChallengeFromUserShouldReturnOnlyOwned(){
        $route = '/user/v3/account_challenges';
        $user = $this->getSignedInUser();

        $resp = $this->requestJson('GET', $route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(),true);
        $elements = $content['data']['elements'];

        foreach ($elements as $element){
            self::assertEquals($user->group_data->id, $element['account']['id']);
            self::assertArrayHasKey('token_reward', $element['challenge']);
        }
    }
}