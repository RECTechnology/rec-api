<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\Campaign;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class CampaignFixture extends Fixture implements DependentFixtureInterface {

    const ACCOUNT_TYPE_PRIVATE = 'PRIVATE';
    const ACCOUNT_TYPE_ORGANIZATION = 'COMPANY';
    const ACCOUNT_SUBTYPE_NORMAL = 'NORMAL';
    const ACCOUNT_SUBTYPE_BMINCOME = 'BMINCOME';
    const ACCOUNT_SUBTYPE_WHOLESALE = 'WHOLESALE';
    const ACCOUNT_SUBTYPE_RETAILER = 'RETAILER';

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        $faker = Factory::create();

        $user = $orm->getRepository(User::class)
            ->findOneBy(['username' => UserFixture::TEST_USER_CREDENTIALS['username']]);

        $campaign = $this->createCampaign($orm, Campaign::BONISSIM_CAMPAIGN_NAME);
        $culture_campaign = $this->createCampaign($orm, Campaign::CULTURE_CAMPAIGN_NAME);

        $this->createRelations($orm, $campaign);

        $orm->flush();
    }


    /**
     * @param ObjectManager $orm
     */
    private function createCampaign(ObjectManager $orm, $campaign_name){
        $campaign = new Campaign();
        $campaign->setName($campaign_name);
        if($campaign_name == Campaign::BONISSIM_CAMPAIGN_NAME){
            $campaign->setTos("private_tos_campaign");
            $campaign->setCode('LTAB20');
        }
        if($campaign_name == Campaign::CULTURE_CAMPAIGN_NAME){
            $campaign->setTos("private_tos_campaign_culture");
            $campaign->setRedeemablePercentage(50);
            $campaign->setMax(100);
            $ltab_account = $orm->getRepository(Group::class)
                ->findOneBy(['name' =>"LTAB"]);
            $campaign->setCampaignAccount($ltab_account->getId());
            $campaign->setCode('CULT21');
        }
        $campaign->setBalance(100 * 1e8);


        $format = 'Y-m-d H:i:s';
        $campaign->setInitDate(DateTime::createFromFormat($format, '2020-10-15 00:00:00'));
        $campaign->setEndDate(DateTime::createFromFormat($format, '2021-11-15 00:00:00'));

        $orm->persist($campaign);
        $orm->flush();
        return $campaign;
    }

    public function getDependencies(){
        return [
            AccountFixture::class,
        ];
    }

    /**
     * @param ObjectManager $orm
     * @param Campaign $campaign
     */
    private function createRelations(ObjectManager $orm, Campaign $campaign): void
    {

        $bonissim_organization_account = $orm->getRepository(Group::class)
            ->findOneBy(['type' => Group::ACCOUNT_TYPE_ORGANIZATION, 'name' => Campaign::BONISSIM_CAMPAIGN_NAME]);

        $bonissim_organization_account_ltab = $orm->getRepository(Group::class)
            ->findOneBy(['type' => Group::ACCOUNT_TYPE_ORGANIZATION, 'name' => AccountFixture::TEST_ACCOUNT_LTAB_COMMERCE['name']]);

        $ltab_account = $orm->getRepository(Group::class)
            ->findOneBy(['name' =>"LTAB"]);

        $bonissim_private_accounts = $orm->getRepository(Group::class)
            ->findBy(['type' => Group::ACCOUNT_TYPE_PRIVATE, 'name' => Campaign::BONISSIM_CAMPAIGN_NAME]);

        $ltab_private_with_store = $orm->getRepository(Group::class)
            ->findOneBy(['name' =>AccountFixture::TEST_ACCOUNT_LTAB_PRIVATE['name'].'_store']);

        $accountsInCampaign = array();
        $accountsInCampaign[] = $bonissim_organization_account;
        $accountsInCampaign[] = $bonissim_organization_account_ltab;
        $accountsInCampaign[] = $ltab_private_with_store;

        foreach ($bonissim_private_accounts as $account){
            $account->setCampaigns([$campaign]);
            $accountsInCampaign[] = $account;
        }
        $bonissim_organization_account->setCampaigns([$campaign]);
        $bonissim_organization_account_ltab->setCampaigns([$campaign]);
        $ltab_private_with_store->setCampaigns([$campaign]);

        $campaign->setAccounts($accountsInCampaign);
        $campaign->setCampaignAccount($ltab_account->getId());

        $orm->persist($campaign);
        $orm->flush();
    }
}