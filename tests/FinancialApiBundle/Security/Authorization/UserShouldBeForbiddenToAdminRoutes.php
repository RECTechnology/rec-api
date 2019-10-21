<?php


namespace Test\FinancialApiBundle\Security\Authorization;


use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class UserStatusCodesTest
 * @package Test\FinancialApiBundle\Security\Authorization
 */
class UserShouldBeForbiddenToAdminRoutes  extends BaseApiTest {

    const ADMIN_PREFIX_PATH = '/admin/v3';

    function setUp(): void {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
    }

    function testIndexAdminCrudv3ReturnsForbidden(){
        foreach (self::CRUD_V3_ROUTES as $route) {
            $resp = $this->requestJson(
                'GET',
                self::ADMIN_PREFIX_PATH . '/' . $route
            );
            self::assertEquals(
                403,
                $resp->getStatusCode(),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );
        }
    }
}