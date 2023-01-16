<?php

namespace Test\FinancialApiBundle\Open;

use App\FinancialApiBundle\DataFixture\AccountFixture;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Activity;
use Test\FinancialApiBundle\BaseApiTest;

class MapTest extends BaseApiTest {

    public function testMapSearchResponds200(){
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

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
            if($account['name'] == AccountFixture::TEST_ACCOUNT_CULT21_COMMERCE['name']){
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

        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $responseSendingTrue = $this->requestJson('GET', '/user/v4/accounts/search?only_with_offers=true');
        $responseSendingOne = $this->requestJson('GET', '/user/v4/accounts/search?only_with_offers=1');

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
        self::assertEquals(3, count($accountsSendingTrue));

        foreach ($accountsSendingTrue['data']['elements'] as $account){
            self::assertEquals(1, $account['has_offers']);
        }

        $accountsSendingOne = json_decode($responseSendingOne->getContent(),true);
        self::assertEquals(3, count($accountsSendingOne));

        foreach ($accountsSendingOne['data']['elements'] as $account){
            self::assertEquals(1, $account['has_offers']);
        }
    }

    public function testMapSearchByCampaignCodeResponds200(){

        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $response = $this->requestJson('GET', '/user/v4/accounts/search?campaign_code=LTAB20');

        self::assertEquals(
            200,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );

        $accounts = json_decode($response->getContent(),true);
        self::assertEquals(3, count($accounts));

        foreach ($accounts['data']['elements'] as $account){
            self::assertArrayHasKey('campaigns', $account);
            $exists_LTAB_campaign = false;
            foreach ($account['campaigns'] as $campaign){
                if($campaign['code'] == 'LTAB20'){
                    $exists_LTAB_campaign = true;
                }
            }
            self::assertEquals(true, $exists_LTAB_campaign);
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

    public function testPublicMapSearchActivity(){
        $response = $this->requestJson('GET', '/public/map/v1/search?activity_id=1');
        $response_content = json_decode($response->getContent(),true);
        self::assertEquals('1', $response_content["data"]["elements"][0]["activity"]);
        foreach ($response_content["data"]["elements"] as $account){
            self::assertEquals(true, $account['is_cultural']);
        }
    }

    public function testMapGreenCommerceResponds200(){
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $resp = $this->requestJson('PUT', '/admin/v3/accounts/6', ["active" => 0]);
        $response = $this->requestJson('GET', '/user/v4/accounts/search');
        self::assertEquals(
            200,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );
        $accounts = json_decode($response->getContent(),true);
        foreach ($accounts["data"]["elements"] as $account){
            if($account['name'] == AccountFixture::TEST_ACCOUNT_REZERO_2['name']){
                self::assertTrue($account["is_commerce_verd"]);
            }
        }
    }

    public function testMapSearchBadgeResponds200(){
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $resp = $this->requestJson('PUT', '/admin/v3/accounts/6', ["active" => 0]);
        $query_string = "?badge_id=1";
        $response = $this->requestJson('GET', '/user/v4/accounts/search'.$query_string);
        self::assertEquals(
            200,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );
        $accounts = json_decode($response->getContent(),true);
        self::assertEquals($accounts["data"]["elements"][0]["badge"], 'Test');
    }
}