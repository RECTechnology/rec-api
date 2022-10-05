<?php

namespace Test\FinancialApiBundle\C2BChallenges;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

class TokenRewardsTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
    }

    function testCreateTokenRewardFromUserShouldFail(){
        $route = '/user/v3/token_rewards';

        $data = array(
            'name' => $this->faker->name,
            'description' => $this->faker->text,
            'status' => 'created',
            'image' => 'https://fakeimage.es/images/1.jpg'
        );
        $resp = $this->requestJson('POST', $route, $data);

        self::assertEquals(
            403,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

    function testUpdateTokenRewardFromUserShouldFail(){
        $route = '/user/v3/token_rewards/1';

        $data = array(
            'name' => 'pollito',
        );
        $resp = $this->requestJson('PUT', $route, $data);

        self::assertEquals(
            403,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

    function testListTokenRewardFromUserShouldFail(){
        $route = '/user/v3/token_rewards';

        $resp = $this->requestJson('GET', $route);

        self::assertEquals(
            403,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

}