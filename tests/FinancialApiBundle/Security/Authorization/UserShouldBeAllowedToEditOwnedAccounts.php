<?php


namespace Test\FinancialApiBundle\Security\Authorization;


use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class UserShouldBeAllowedToEditOwnedAccounts
 * @package Test\FinancialApiBundle\Security\Authorization
 */
class UserShouldBeAllowedToEditOwnedAccounts  extends BaseApiTest {

    function setUp(): void {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
    }

    function testUpdateOwnedAccountShouldSuccessToUser(){

        $route = '/user/v1/account';
        $resp = $this->requestJson(
            'GET',
            $route
        );
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $accounts = json_decode($resp->getContent())->data->accounts;

        foreach ($accounts as $account) {
            $route = '/user/v3/accounts/' . $account->id;
            $resp = $this->requestJson(
                'PUT',
                $route,
                ['name' => 'my name 69']
            );
            self::assertNotEquals(
                403,
                $resp->getStatusCode(),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );
        }

    }
}