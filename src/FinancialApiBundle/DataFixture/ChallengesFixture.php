<?php

namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\Badge;
use App\FinancialApiBundle\Entity\Challenge;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\TokenReward;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class ChallengesFixture extends Fixture implements DependentFixtureInterface
{

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        $today = new \DateTime();
        $three_days_after = new \DateTime('+3 days');
        $six_days_after = new \DateTime('+6 days');
        $three_days_before = new \DateTime('-3 days');
        $this->createChallenge($orm, null, Challenge::STATUS_SCHEDULED, $three_days_after, $six_days_after);
        $token1 = $orm->getRepository(TokenReward::class)->find(1);
        $this->createChallenge($orm, $token1, Challenge::STATUS_OPEN, $three_days_before, $three_days_after);
        $this->createChallenge($orm, null, Challenge::STATUS_CLOSED, $three_days_after, $six_days_after);
        $token2 = $orm->getRepository(TokenReward::class)->find(2);
        $this->createChallenge($orm, $token2, Challenge::STATUS_OPEN, $three_days_before, $three_days_after);
    }

    /**
     * @param ObjectManager $orm
     */
    private function createChallenge(ObjectManager $orm, ?TokenReward $token, $status, $start, $finish){
        $faker = Factory::create();

        $challenge = new Challenge();
        $challenge->setTitle($faker->name);
        $challenge->setStatus($status);
        $challenge->setDescription($faker->text);
        $challenge->setAction(Challenge::ACTION_TYPE_BUY);
        $challenge->setAmountRequired(0);
        $challenge->setCoverImage('https://fakeimage.com/images/1');
        $challenge->setStartDate($start);
        $challenge->setFinishDate($finish);
        $challenge->setThreshold(1);
        $challenge->setType(Challenge::TYPE_CHALLENGE);

        $challenge->setTokenReward($token);

        $adminAccount = $orm->getRepository(Group::class)->find(7);
        $challenge->setOwner($adminAccount);

        $orm->persist($challenge);
        $orm->flush();
    }

    public function getDependencies(){
        return [
            AccountFixture::class,
            TokenRewardsFixture::class
        ];
    }

}