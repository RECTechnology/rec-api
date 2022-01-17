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

        $ltab_campaign = $this->createCampaign($orm, Campaign::BONISSIM_CAMPAIGN_NAME);
        $culture_campaign = $this->createCampaign($orm, Campaign::CULTURE_CAMPAIGN_NAME);

        $this->createRelations($orm, $ltab_campaign, $culture_campaign);

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
                ->findOneBy(['name' =>"CULT21"]);
            $campaign->setCampaignAccount($ltab_account->getId());
            $campaign->setCode('CULT21');
        }
        $campaign->setBalance(100 * 1e8);


        $format = 'Y-m-d H:i:s';
        $campaign->setInitDate(DateTime::createFromFormat($format, '2020-10-15 00:00:00'));
        $campaign->setEndDate(DateTime::createFromFormat($format, '2030-11-15 00:00:00'));

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
     * @param Campaign $ltab_campaign
     * @param Campaign $culture_campaign
     */
    private function createRelations(ObjectManager $orm, Campaign $ltab_campaign, Campaign $culture_campaign): void
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

        $culture_organization_account = $orm->getRepository(Group::class)
            ->findOneBy(['type' => Group::ACCOUNT_TYPE_ORGANIZATION, 'name' => AccountFixture::TEST_ACCOUNT_CULT21_COMMERCE['name']]);

        $culture_admin_account = $orm->getRepository(Group::class)
            ->findOneBy(['name' => 'CULT21']);

        $culture_private_account = $orm->getRepository(Group::class)
            ->findOneBy(['name' =>Campaign::CULTURE_CAMPAIGN_NAME]);

        $accountsInCampaign = array();
        $accountsInCampaign[] = $bonissim_organization_account;
        $accountsInCampaign[] = $bonissim_organization_account_ltab;
        $accountsInCampaign[] = $ltab_private_with_store;

        foreach ($bonissim_private_accounts as $account){
            $account->setCampaigns([$ltab_campaign]);
            $accountsInCampaign[] = $account;
        }
        $bonissim_organization_account->setCampaigns([$ltab_campaign]);
        $bonissim_organization_account_ltab->setCampaigns([$ltab_campaign]);
        $ltab_private_with_store->setCampaigns([$ltab_campaign]);


        $ltab_campaign->setAccounts($accountsInCampaign);
        $ltab_campaign->setCampaignAccount($ltab_account->getId());

        $culture_admin_account->setCampaigns([$culture_campaign]);
        $culture_organization_account->setCampaigns([$culture_campaign]);
        $culture_private_account->setCampaigns([$culture_campaign]);
        $culture_campaign->setAccounts([$culture_organization_account, $culture_admin_account, $culture_private_account]);

        $orm->persist($ltab_campaign);
        $orm->persist($culture_campaign);
        $orm->flush();
    }
}