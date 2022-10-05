<?php

namespace Test\FinancialApiBundle\Admin\C2BChallenges;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

class TokenRewardsTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testCreateTokenRewardFromSuperShouldWork(){
        $route = '/admin/v3/token_rewards';

        $data = array(
            'name' => $this->faker->name,
            'description' => $this->faker->text,
            'status' => 'created',
            'image' => 'https://fakeimage.es/images/1.jpg'
        );
        $resp = $this->requestJson('POST', $route, $data);

        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );


    }

    function testUpdateTokenRewardFromSuperShouldWork(){
        $route = '/admin/v3/token_rewards/1';

        $data = array(
            'name' => 'pollito',
        );
        $resp = $this->requestJson('PUT', $route, $data);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent());

        self::assertEquals('pollito', $content->data->name);
    }

    function testDeleteTokenRewardFromSuperShouldWork(){

        $route = '/admin/v3/token_rewards/1';

        $resp = $this->requestJson('DELETE', $route);

        self::assertEquals(
            204,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

}