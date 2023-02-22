<?php

namespace App\Tests\Bugs;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class AccountValidationsShouldWorkTest
 * @package App\Tests\Bugs
 */
class AccountValidationsShouldWorkTest extends BaseApiTest {

    private $account;

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
        $route = '/user/v3/accounts';
        $resp = $this->requestJson('GET', $route);

        $this->account = json_decode($resp->getContent())->data->elements[0];
    }

    function testCountryNotValid()
    {

        $route = "/user/v3/accounts/{$this->account->id}";
        $resp = $this->requestJson('PUT', $route, ['country' => 'not-valid']);
        self::assertEquals(
            400,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

    function testCountryValid()
    {
        $route = "/user/v3/accounts/{$this->account->id}";
        $resp = $this->requestJson('PUT', $route, ['country' => 'spa']);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

    function testLatitudeAndLongitudeNotValid()
    {
        $route = "/user/v3/accounts/{$this->account->id}";
        $resp = $this->requestJson('PUT', $route, ['latitude' => "", 'longitude' => "not-valid"]);
        self::assertEquals(
            400,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

    function testLatitudeAndLongitudeValid()
    {
        $route = "/user/v3/accounts/{$this->account->id}";
        $resp = $this->requestJson('PUT', $route, ['latitude' => 0.22233, 'longitude' => 40.22]);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

}
