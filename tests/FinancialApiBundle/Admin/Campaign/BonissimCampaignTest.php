<?php

namespace Test\FinancialApiBundle\Admin\Campaign;

use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\Group;
use DateTime;
use Test\FinancialApiBundle\Admin\AdminApiTest;



/**
 * Class BonissimCampaignTest
 * @package Test\FinancialApiBundle\Admin\Campaign
 */
class BonissimCampaignTest extends AdminApiTest {

    private function createCampaign($campaign_name){
        $campaign = new Campaign();
        $campaign->setName($campaign_name);
        $campaign->setBalance(100 * 1e8);

        $format = 'Y-m-d H:i:s';
        $campaign->setInitDate(DateTime::createFromFormat($format, '2020-09-15 00:00:00'));
        $campaign->setEndDate(DateTime::createFromFormat($format, '2020-10-15 00:00:00'));
        $em = $this->client->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($campaign);
        $em->flush();

    }

    private function getFromRoute($route){
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(), true);
        return $content;
    }

    function testDeleteRelation(){
        $campaign = $this->rest('GET', "/admin/v3/campaigns?name=".Campaign::BONISSIM_CAMPAIGN_NAME)[0];
        self::assertTrue(isset($campaign));
        $bonissim_account = $this->rest('GET', "/admin/v3/accounts?campaigns=".$campaign->id)[0];
        self::assertTrue(isset($bonissim_account));

        $route = '/admin/v3/accounts/'.$bonissim_account->id.'/campaigns/'.$campaign->id;
        $resp = $this->requestJson('DELETE', $route);
        self::assertEquals(204, $resp->getStatusCode());

        $campaign = $this->rest('GET', "/admin/v3/campaign/".$campaign->id);
        self::assertTrue(isset($campaign));
        $bonissim_account = $this->rest('GET', "/admin/v3/group/".$bonissim_account->id);
        self::assertTrue(isset($bonissim_account));

        self::assertCount(0, $bonissim_account->campaigns);

        foreach($campaign->accounts as $account){
            self::assertFalse($bonissim_account->id == $account->id);
        }
    }

    function testAddRelation(){

        $campaign = $this->rest('GET', "/admin/v3/campaigns?name=".Campaign::BONISSIM_CAMPAIGN_NAME)[0];
        self::assertTrue(isset($campaign));

        $commerces =  $this->rest('GET', "/user/v3/accounts?type=COMPANY");
        self::assertGreaterThanOrEqual(1, count($commerces));

        foreach($commerces as $commerce) {
            if($commerce->name != Campaign::BONISSIM_CAMPAIGN_NAME){
                $not_bonissim_account = $commerce;
            }
        }
        self::assertTrue(isset($not_bonissim_account));

        $route = '/admin/v3/accounts/'.$not_bonissim_account->id.'/campaigns';
        $resp = $this->requestJson('POST', $route, ["id" => $campaign->id]);
        self::assertEquals(201, $resp->getStatusCode());


        $campaign = $this->rest('GET', "/admin/v3/campaign/".$campaign->id);
        self::assertTrue(isset($campaign));
        $not_bonissim_account = $this->rest('GET', "/admin/v3/group/".$not_bonissim_account->id);
        self::assertTrue(isset($not_bonissim_account));

        self::assertCount(1, $not_bonissim_account->campaigns);

        $relation = false;
        foreach($campaign->accounts as $account){
            if($not_bonissim_account->id == $account->id){
                $relation = true;
            }
        }
        self::assertTrue($relation);
    }


    function testCreateBonissimAcount(){
        $this->createCampaign(Campaign::BONISSIM_CAMPAIGN_NAME);
        $this->client->getKernel()->getContainer()->get('bonissim_service')->CreateBonissimAccount(1, Campaign::BONISSIM_CAMPAIGN_NAME);

        $resp = $this->requestJson('GET', '/admin/v3/campaigns', ["name" => Campaign::BONISSIM_CAMPAIGN_NAME]);
        self::assertEquals(200, $resp->getStatusCode());

        $resp = $this->requestJson('GET', '/user/v1/account');
   }
  
    function testSetUserTOS(){
        $user = $this->getFromRoute('/admin/v3/user/1');
        self::assertFalse($user['data']['private_tos_campaign']);
        $resp = $this->requestJson('PUT', '/admin/v3/user/1', ["private_tos_campaign" => 1]);
        $user = $this->getFromRoute('/admin/v3/user/1');
        self::assertTrue($user['data']['private_tos_campaign']);
    }

}