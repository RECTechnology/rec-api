<?php


namespace Test\FinancialApiBundle\Bugs;


use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class Issue230Test
 * @package Test\FinancialApiBundle\Bugs
 * @see https://github.com/QbitArtifacts/rec-api/issues/230
 */
class Issue230Test extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testBadValues(){
        $account = $this->fetchOneAccount();
        $route = "/account/{$account->id}/v1/location";
        $params = ['deactivate' => 0, 'longitude' => -99.0355973, 'latitude' => 39.9875151];
        $resp = $this->requestJson('PUT', $route, $params);
        self::assertEquals(
            400,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $params = ['deactivate' => 0, 'longitude' => -9.0355973, 'latitude' => 99.9875151];
        $resp = $this->requestJson('PUT', $route, $params);
        self::assertEquals(
            400,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

    function testIssueIsSolved(){
        $account = $this->fetchOneAccount();
        $route = "/account/{$account->id}/v1/location";
        $params = ['deactivate' => 0, 'longitude' => -0.0355973, 'latitude' => 39.9875151];
        $resp = $this->requestJson('PUT', $route, $params);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }


    function fetchOneAccount(){
        $route = "/admin/v3/accounts";
        $resp = $this->requestJson('GET', $route);
        return json_decode($resp->getContent())->data->elements[0];

    }
}