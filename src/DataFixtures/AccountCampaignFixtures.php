<?php

namespace App\DataFixtures;

use App\Entity\AccountCampaign;
use App\Entity\Campaign;
use App\Entity\Group;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AccountCampaignFixtures extends Fixture implements DependentFixtureInterface
{

    public function getDependencies()
    {
        return [
            AccountFixtures::class,
            CampaignFixtures::class
        ];
    }

    public function load(ObjectManager $manager)
    {
        $accounts = $manager->getRepository(Group::class)->findBy(array('type' => Group::ACCOUNT_TYPE_PRIVATE));

        /** @var Campaign $roses_campaign */
        $roses_campaign = $manager->getRepository(Campaign::class)->findOneBy(array('name' => 'ROSES'));

        /** @var Group $account */
        foreach ($accounts as $account){
            $account_campaign = $this->addAccountToCampaign($account, $roses_campaign);
            $manager->persist($account_campaign);
        }
        $manager->flush();

    }

    private function addAccountToCampaign(Group $account, Campaign $campaign){
        $account_campaign = new AccountCampaign();
        $account_campaign->setCampaign($campaign);
        $account_campaign->setAccount($account);

        return $account_campaign;
    }
}