<?php

namespace App\Tests\Open;

use App\DataFixtures\AccountFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\Activity;
use App\Tests\BaseApiTest;

class MapTest extends BaseApiTest {

    private const ENDPOINT_ROLES = ['user', 'public'];
    public function testMapSearchResponds200(){
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);

        $resp = $this->requestJson('PUT', '/admin/v3/accounts/6', ["active" => 0]);
        $query_string = "?activity_id=2";
        $response = $this->requestJson('GET', '/user/v4/accounts/search'.$query_string);
        self::assertEquals(
            200,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );
        self::assertFalse(str_contains($response->getContent(), '"id":6,'));
        $accounts = json_decode($response->getContent(),true);
        foreach ($accounts['data']['elements'] as $account){
            self::assertArrayHasKey('is_cultural', $account);
            if($account['name'] === AccountFixtures::TEST_ACCOUNT_CULT21_COMMERCE['name']){
                self::assertFalse($account['has_offers']);
            }
        }

        $query_string = "?activity_id=1";
        $response = $this->requestJson('GET', '/user/v4/accounts/search'.$query_string);
        self::assertEquals(
            200,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );

        $accounts = json_decode($response->getContent(),true);
        foreach ($accounts['data']['elements'] as $account){
            self::assertArrayHasKey('is_cultural', $account);
            self::assertEquals(true, $account['is_cultural']);
        }
    }

    public function testMapSearchOnlyWithOffersResponds200(){

        foreach (self::ENDPOINT_ROLES as $role) {
            if ($role !== 'public') {
                $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
            }

            $responseSendingTrue = $this->requestJson('GET', '/'.$role.'/v4/accounts/search?only_with_offers=true');
            $responseSendingOne = $this->requestJson('GET', '/'.$role.'/v4/accounts/search?only_with_offers=1');

            self::assertEquals(
                200,
                $responseSendingTrue->getStatusCode(),
                "status_code: {$responseSendingTrue->getStatusCode()} content: {$responseSendingTrue->getContent()}"
            );

            self::assertEquals(
                200,
                $responseSendingOne->getStatusCode(),
                "status_code: {$responseSendingOne->getStatusCode()} content: {$responseSendingOne->getContent()}"
            );


            $accountsSendingTrue = json_decode($responseSendingTrue->getContent(),true);
            self::assertCount(3, $accountsSendingTrue);

            foreach ($accountsSendingTrue['data']['elements'] as $account){
                self::assertEquals(1, $account['has_offers']);

            }

            $accountsSendingOne = json_decode($responseSendingOne->getContent(),true);
            self::assertCount(3, $accountsSendingOne);

            foreach ($accountsSendingOne['data']['elements'] as $account){
                self::assertEquals(1, $account['has_offers']);
            }

            if ($role !== 'public') {
                $this->signOut();
            }
        }

    }

    public function testMapSearchByCampaignCodeResponds200(){

        foreach (self::ENDPOINT_ROLES as $role){
            if($role !== 'public'){
                $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
            }

            $response = $this->requestJson('GET', '/'.$role.'/v4/accounts/search?campaign_code=LTAB20');

            self::assertEquals(
                200,
                $response->getStatusCode(),
                "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
            );

            $accounts = json_decode($response->getContent(),true);
            self::assertCount(3, $accounts);

            foreach ($accounts['data']['elements'] as $account){
                self::assertArrayHasKey('campaigns', $account);
                $exists_LTAB_campaign = false;
                foreach ($account['campaigns'] as $campaign){
                    if($campaign['code'] === 'LTAB20'){
                        $exists_LTAB_campaign = true;
                    }
                }
                self::assertEquals(true, $exists_LTAB_campaign);
            }
            if($role !== 'public'){
                $this->signOut();
            }
        }

    }

    public function testPublicMapSearchOffers(){
        $response = $this->requestJson('GET', '/public/map/v1/search?only_with_offers=1');
        $response_content = json_decode($response->getContent(),true);
        $accounts = $response_content['data']['elements'];
        foreach ($accounts as $account){
            self::assertTrue($account["offers"][0]["active"]);
            self::assertArrayHasKey('is_commerce_verd', $account);
            self::assertArrayHasKey('is_cultural', $account);
        }
    }

    public function testPublicMapSearchCampaign(){
        $response = $this->requestJson('GET', '/public/map/v1/search?campaign_code=LTAB20');
        $response_content = json_decode($response->getContent(),true);
        self::assertEquals('LTAB20', $response_content['data']['elements'][0]['campaign']);
    }

    public function testPublicAndUserMapSearchActivity(){
        $public_response = $this->requestJson('GET', '/public/map/v1/search?activity_id=1');
        $public_response_content = json_decode($public_response->getContent(),true);
        self::assertEquals('4', $public_response_content["data"]["total"]);


        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
        $response = $this->requestJson('GET', '/user/v4/accounts/search?activity_id=1');
        $response_content = json_decode($response->getContent(),true);
        self::assertEquals('4', $response_content["data"]["total"]);
    }

    public function testMapGreenCommerceResponds200(){
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);

        $resp = $this->requestJson('PUT', '/admin/v3/accounts/6', ["active" => 0]);
        $response = $this->requestJson('GET', '/user/v4/accounts/search');
        self::assertEquals(
            200,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );
        $accounts = json_decode($response->getContent(),true);
        foreach ($accounts["data"]["elements"] as $account){
            if($account['name'] === AccountFixtures::TEST_ACCOUNT_REZERO_2['name']){
                self::assertTrue($account["is_commerce_verd"]);
            }
        }
    }

    public function testMapSearchBadgeResponds200(){
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);

        $resp = $this->requestJson('PUT', '/admin/v3/accounts/6', ["active" => 0]);
        $query_string = "?badge_id=1";
        $response = $this->requestJson('GET', '/user/v4/accounts/search'.$query_string);
        self::assertEquals(
            200,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );
        $accounts = json_decode($response->getContent(),true);
        self::assertEquals('Test', $accounts["data"]["elements"][0]["badge"]);
    }
}