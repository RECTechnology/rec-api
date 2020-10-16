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

    private function createCampaign(){
        $campaign = new Campaign();
        $campaign->setName("Bonissim");
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
        $this->createCampaign();
        $em = $this->client->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => 'Bonissim']);

        $user_accounts = $em->getRepository(Group::class)->findBy(['kyc_manager' => 2]);
        $campaign_accounts = $campaign->getAccounts();
        $user_private_accounts = 0;
        foreach ($user_accounts as $account){
            $account_campaigns = $account->getCampaigns();
            if($account->getType() == "PRIVATE"){
                $account_campaigns->add($campaign);
                $campaign_accounts->add($account);
                $em->persist($campaign);
                $em->persist($account);
                $em->flush();
                $user_private_accounts += 1;
                self::assertCount(1, $account->getCampaigns());
            }
        }

        $route = '/admin/v3/accounts/2/campaigns/1';
        $resp = $this->requestJson('DELETE', $route);
        self::assertEquals(204, $resp->getStatusCode());
        $rest_campaigns = $this->getFromRoute('/admin/v3/campaigns');
        self::assertCount($user_private_accounts - 1, $rest_campaigns['data']['elements'][0]['accounts']);
        $rest_group = $this->getFromRoute('/admin/v3/group/2');
        self::assertCount(0, $rest_group['data']['campaigns']);
    }

    function testAddRelation(){
        $this->createCampaign();
        $resp = $this->requestJson('POST', '/admin/v3/accounts/2/campaigns', ["id" => 1]);
        self::assertEquals(201, $resp->getStatusCode());

        $resp = $this->requestJson('GET', '/admin/v3/accounts/2');
        self::assertEquals(200, $resp->getStatusCode());
        $content = json_decode($resp->getContent(), true);
        self::assertCount(1, $content["data"]['campaigns']);
    }

    function testSetUserTOS(){
        $user = $this->getFromRoute('/admin/v3/user/1');
        self::assertFalse($user['data']['private_tos_campaign']);
        $resp = $this->requestJson('PUT', '/admin/v3/user/1', ["private_tos_campaign" => 1]);
        $user = $this->getFromRoute('/admin/v3/user/1');
        self::assertTrue($user['data']['private_tos_campaign']);

    }

}