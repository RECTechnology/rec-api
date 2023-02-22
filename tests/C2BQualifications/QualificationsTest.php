<?php

namespace App\Tests\C2BQualifications;

use App\DataFixtures\UserFixtures;
use App\Entity\Qualification;
use App\Tests\BaseApiTest;

class QualificationsTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_THIRD_USER_CREDENTIALS);
    }

    function testListPendingAndQualifyShouldWork(){
        //GET all pending qualifications
        $route = '/user/v3/qualifications?status=pending';
        $resp = $this->requestJson('GET', $route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(),true);
        $pendingElements = $content['data']['elements'];
        self::assertGreaterThan(10, $content['data']['total']);
        foreach ($pendingElements as $qualification){
            self::assertEquals(Qualification::STATUS_PENDING, $qualification['status']);
            self::assertArrayHasKey('public_image', $qualification['account']);
            self::assertArrayHasKey('company_image', $qualification['account']);
        }

        $data = array('value' => 1);

        //user qualify shop, only one element
        $routeUpdate = '/user/v3/qualifications/'.$pendingElements[0]['id'];
        $respUpdate = $this->requestJson('PUT', $routeUpdate, $data);
        self::assertEquals(
            200,
            $respUpdate->getStatusCode(),
            "route: $routeUpdate, status_code: {$respUpdate->getStatusCode()}, content: {$respUpdate->getContent()}"
        );

        $updatedContent = json_decode($respUpdate->getContent(),true);

        $dataResp = $updatedContent["data"];
        self::assertEquals(true, $dataResp['value']);
        self::assertEquals(Qualification::STATUS_REVIEWED, $dataResp['status']);

        $resp = $this->requestJson('GET', $route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(),true);
        self::assertEquals(10, $content['data']['total']);

        //check shop badge, should have one because min_qualifications is 0
        $routeGetShop = 'user/v3/accounts/'.$pendingElements[0]['account']['id'];
        $respGetShop = $this->requestJson('GET', $routeGetShop);

        self::assertEquals(
            200,
            $respGetShop->getStatusCode(),
            "route: $route, status_code: {$respGetShop->getStatusCode()}, content: {$respGetShop->getContent()}"
        );

        $content = json_decode($respGetShop->getContent(),true);
        $data = $content['data'];
        self::assertEquals($pendingElements[0]['id'], $data['badges'][0]['id']);

        //change min_qualifications to 5
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
        $routeGetSettings = 'admin/v3/configuration_settings';
        $respGetSettings = $this->requestJson('GET', $routeGetSettings);

        $contentSettings = json_decode($respGetSettings->getContent(),true);
        $elements = $contentSettings['data']['elements'];
        foreach ($elements as $element){
            if($element['name'] === 'min_qualifications'){
                $updatedData = array(
                    'value' => 5
                );
                $respUpdateSettings = $this->requestJson('PUT', $routeGetSettings.'/'.$element['id'], $updatedData);
                $contentSettingsUpdated = json_decode($respUpdateSettings->getContent(),true);
                $data = $contentSettingsUpdated['data'];

            }
        }

        $this->signIn(UserFixtures::TEST_THIRD_USER_CREDENTIALS);

        $data = array('value' => 1);

        //user qualify shop, elements 2
        $routeUpdate = '/user/v3/qualifications/'.$pendingElements[1]['id'];
        $respUpdate = $this->requestJson('PUT', $routeUpdate, $data);
        self::assertEquals(
            200,
            $respUpdate->getStatusCode(),
            "route: $routeUpdate, status_code: {$respUpdate->getStatusCode()}, content: {$respUpdate->getContent()}"
        );

        $routeGetShop = 'user/v3/accounts/'.$pendingElements[1]['account']['id'];
        $respGetShop = $this->requestJson('GET', $routeGetShop);

        self::assertEquals(
            200,
            $respGetShop->getStatusCode(),
            "route: $route, status_code: {$respGetShop->getStatusCode()}, content: {$respGetShop->getContent()}"
        );

        $content = json_decode($respGetShop->getContent(),true);
        $data = $content['data'];
        $badges = $data['badges'];
        //check that only has one badge, after changing min_qualifications to 5, it's not generated in the first one like before
        self::assertEquals(1, count($badges));
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