<?php


namespace App\Tests\Security\Authorization;


use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class UserShouldBeAllowedToEditOwnedAccounts
 * @package App\Tests\Security\Authorization
 */
class UserShouldBeAllowedToEditOwnedAccounts  extends BaseApiTest {

    function setUp(): void {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
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

    function testUpdateNotOwnedAccountShouldForbidUser(){

        $route = '/user/v3/accounts';
        $resp = $this->requestJson(
            'GET',
            $route
        );
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $allAccounts = json_decode($resp->getContent())->data->elements;

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
        $myAccounts = json_decode($resp->getContent())->data->accounts;
        $foreignAccounts = [];
        foreach ($allAccounts as $foreign){
            $isOwned = false;
            foreach ($myAccounts as $owned){
                if($foreign->id == $owned->id){
                    $isOwned = true;
                    break;
                }
            }
            if(!$isOwned) $foreignAccounts []= $foreign;
        }

        foreach ($foreignAccounts as $account) {
            if($account->id)
                $route = '/user/v3/accounts/' . $account->id;
            $resp = $this->requestJson(
                'PUT',
                $route,
                ['name' => 'my name 69']
            );
            self::assertEquals(
                403,
                $resp->getStatusCode(),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );
        }

    }
}