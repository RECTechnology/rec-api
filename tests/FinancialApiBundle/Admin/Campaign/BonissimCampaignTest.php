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


    function testGetCampaigns(){
        $this->createCampaign();
        $campaigns = $this->getFromRoute('/admin/v3/campaigns');
    }

    function testAccountCampaigns(){
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
        $rest_campaigns = $this->getFromRoute('/admin/v3/campaigns');
        self::assertCount($user_private_accounts, $rest_campaigns['data']['elements'][0]['accounts']);

    }
}