<?php

namespace App\DataFixtures;

use App\Entity\AccountChallenge;
use App\Entity\Badge;
use App\Entity\Challenge;
use App\Entity\Group;
use App\Entity\TokenReward;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class AccountChallengesFixtures extends Fixture implements DependentFixtureInterface
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
        if($challenge){
            $this->createAccountChallengeForAllAccounts($orm, $challenge);
        }
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
            AccountFixtures::class,
            ChallengesFixtures::class
        ];
    }

}