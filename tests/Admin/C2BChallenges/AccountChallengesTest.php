<?php

namespace App\Tests\Admin\C2BChallenges;

use App\DataFixtures\UserFixtures;
use App\Entity\Challenge;
use App\Tests\BaseApiTest;

class AccountChallengesTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
    }

    function testGetAccountChallengesFromSuperShouldWork(){
        $route = '/admin/v3/account_challenges';

        $resp = $this->requestJson('GET', $route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(),true);
        self::assertEquals(23, $content['data']['total']);
    }

}