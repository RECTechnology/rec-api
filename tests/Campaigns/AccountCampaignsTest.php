<?php

namespace App\Tests\Campaigns;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class AccountCampaignsTest
 * @package App\Tests\Campaign
 */
class AccountCampaignsTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
    }

    function testIndexShouldReturnOnlyOwnedCampaigns()
    {
        $resp = $this->requestJson('GET', '/user/v3/account_campaign');
        self::assertEquals(
            200,
            $resp->getStatusCode()
        );

        $content = json_decode($resp->getContent(),true);
        $data = $content['data'];

        $user = $this->getSignedInUser();
        $current_account_id = $user->group_data->id;
        self::assertCount(1, $data['elements']);
        foreach ($data['elements'] as $element){
            self::assertEquals($current_account_id, $element['account']['id']);
        }

    }

    function testIndexShouldReturnOnlyActiveOwnedCampaigns()
    {
        $resp = $this->requestJson('GET', '/user/v3/account_campaigns?only_active_campaigns=true');
        self::assertResponseIsSuccessful();

        $content = json_decode($resp->getContent(),true);
        $data = $content['data'];

        $user = $this->getSignedInUser();
        $current_account_id = $user->group_data->id;
        self::assertCount(1, $data['elements']);
        self::assertArrayHasKey('total_accumulated_bonus', $data);
        self::assertArrayHasKey('total_spent_bonus', $data);
        foreach ($data['elements'] as $element){
            self::assertEquals($current_account_id, $element['account']['id']);
            $finish_date = new \DateTime($element['campaign']['end_date']);
            $today = new \DateTime();
            self::assertGreaterThan($today, $finish_date);
        }

    }

    function testAcceptTos2TimesAtSameCampaignShouldNotCreateNewRelation(){

        $resp = $this->requestJson('PUT', '/user/v4/campaign/accept_tos', ["campaign_code" => 'ROSA_CODE']);
        self::assertEquals(204, $resp->getStatusCode());

    }

}
