<?php

namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\AccountChallenge;
use App\FinancialApiBundle\Entity\Badge;
use App\FinancialApiBundle\Entity\Challenge;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\TokenReward;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class AccountChallengesFixture extends Fixture implements DependentFixtureInterface
{

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        $challenge = $orm->getRepository(Challenge::class)->find(4);
        $this->createAccountChallengeForAllAccounts($orm, $challenge);
    }

    /**
     * @param ObjectManager $orm
     */
    private function createAccountChallengeForAllAccounts(ObjectManager $orm, Challenge $challenge){
        $accounts = $orm->getRepository(Group::class)->findAll();
        foreach ($accounts as $account){
            $account_challenge = new AccountChallenge();
            $account_challenge->setAccount($account);
            $account_challenge->setChallenge($challenge);
            $orm->persist($account_challenge);
            $orm->flush();
        }
    }

    public function getDependencies(){
        return [
            AccountFixture::class,
            ChallengesFixture::class
        ];
    }

}