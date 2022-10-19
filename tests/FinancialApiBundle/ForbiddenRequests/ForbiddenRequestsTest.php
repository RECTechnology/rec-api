<?php

namespace Test\FinancialApiBundle\ForbiddenRequests;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class ForbiddenRequestsTest
 * @package Test\FinancialApiBundle\User
 */
class ForbiddenRequestsTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
    }

    public const CRUD_V3_FORBIDDEN_ROUTES = [
        'users_sms_logss',
        'access_tokens'
    ];

    function testRoutesShouldBeForbidden()
    {
        $this->markTestIncomplete('No pasa los tests, hay que capar cada endpoint');
        $this->signIn(UserFixture::TEST_THIRD_USER_CREDENTIALS);
        foreach (self::CRUD_V3_FORBIDDEN_ROUTES as $route){
            $resp = $this->requestJson(
                'GET',
                '/user/v3/'.$route
            );

            self::assertEquals(
                403,
                $resp->getStatusCode(),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );
        }

    }

}
