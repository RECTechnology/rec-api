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

        $campaign = $this->createCampaign($orm);
        $this->createRelations($orm, $campaign);

        $orm->flush();
    }


    /**
     * @param ObjectManager $orm
     */
    private function createCampaign(ObjectManager $orm){
        $campaign = new Campaign();
        $campaign->setName(Campaign::BONISSIM_CAMPAIGN_NAME);
        $campaign->setBalance(100 * 1e8);

        $format = 'Y-m-d H:i:s';
        $campaign->setInitDate(DateTime::createFromFormat($format, '2020-10-15 00:00:00'));
        $campaign->setEndDate(DateTime::createFromFormat($format, '2021-11-15 00:00:00'));
        $campaign->setImageUrl('');

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
        $bonissim_private_account = $orm->getRepository(Group::class)
            ->findOneBy(['type' => Group::ACCOUNT_TYPE_PRIVATE, 'name' => Campaign::BONISSIM_CAMPAIGN_NAME]);
        $bonissim_organization_account = $orm->getRepository(Group::class)
            ->findOneBy(['type' => Group::ACCOUNT_TYPE_ORGANIZATION, 'name' => Campaign::BONISSIM_CAMPAIGN_NAME]);

        $ltab_account = $orm->getRepository(Group::class)
            ->findOneBy(['name' =>"LTAB"]);

        $bonissim_private_account->setCampaigns([$campaign]);
        $bonissim_organization_account->setCampaigns([$campaign]);
        $campaign->setAccounts([$bonissim_private_account, $bonissim_organization_account]);
        $campaign->setCampaignAccount($ltab_account->getId());

        $orm->persist($campaign);
        $orm->persist($bonissim_private_account);
        $orm->persist($bonissim_organization_account);
        $orm->flush();
    }
}