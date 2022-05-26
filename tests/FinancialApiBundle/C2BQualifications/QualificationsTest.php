<?php

namespace Test\FinancialApiBundle\C2BQualifications;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Qualification;
use Test\FinancialApiBundle\BaseApiTest;

class QualificationsTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_THIRD_USER_CREDENTIALS);
    }

    function testListPendingAndQualifyShouldWork(){
        $route = '/user/v3/qualifications?status=pending';
        $resp = $this->requestJson('GET', $route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(),true);
        $elements = $content['data']['elements'];
        self::assertEquals(10, $content['data']['total']);
        foreach ($elements as $qualification){
            self::assertEquals(Qualification::STATUS_PENDING, $qualification['status']);
        }

        $data = array('value' => 1);

        //user qualify shop
        $routeUpdate = '/user/v3/qualifications/'.$elements[0]['id'];
        $respUpdate = $this->requestJson('PUT', $routeUpdate, $data);
        self::assertEquals(
            200,
            $respUpdate->getStatusCode(),
            "route: $routeUpdate, status_code: {$respUpdate->getStatusCode()}, content: {$respUpdate->getContent()}"
        );

        $updatedContent = json_decode($respUpdate->getContent(),true);

        $data = $updatedContent["data"];
        self::assertEquals(true, $data['value']);
        self::assertEquals(Qualification::STATUS_REVIEWED, $data['status']);

        $resp = $this->requestJson('GET', $route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(),true);
        self::assertEquals(9, $content['data']['total']);

        //check shop badge
        $routeGetShop = 'user/v3/accounts/'.$elements[0]['account']['id'];
        $respGetShop = $this->requestJson('GET', $routeGetShop);

        self::assertEquals(
            200,
            $respGetShop->getStatusCode(),
            "route: $route, status_code: {$respGetShop->getStatusCode()}, content: {$respGetShop->getContent()}"
        );

        $content = json_decode($respGetShop->getContent(),true);
        $data = $content['data'];
        self::assertEquals($elements[0]['id'], $data['badges'][0]['id']);

    }

    public function testCreateQualificationShouldFail(){
        $route = '/user/v3/qualifications';
        $data = array(
            "status" => "created"
        );
        $resp = $this->requestJson('POST', $route, $data);

        self::assertEquals(
            403,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

}