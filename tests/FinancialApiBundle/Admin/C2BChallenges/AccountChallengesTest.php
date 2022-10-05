<?php

namespace Test\FinancialApiBundle\Admin\C2BChallenges;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Challenge;
use Test\FinancialApiBundle\BaseApiTest;

class AccountChallengesTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
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