<?php

namespace Test\FinancialApiBundle\Campaigns;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3ReadTestInterface;

/**
 * Class AccountCampaignsTest
 * @package Test\FinancialApiBundle\Campaign
 */
class AccountCampaignsTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
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
        $resp = $this->requestJson('GET', '/user/v3/account_campaign?only_active_campaigns=true');
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
            $finish_date = new \DateTime($element['campaign']['end_date']);
            $today = new \DateTime();
            self::assertGreaterThan($today, $finish_date);
        }

    }

}
