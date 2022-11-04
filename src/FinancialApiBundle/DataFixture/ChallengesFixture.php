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
        $one_days_before = new \DateTime('-1 days');
        $this->createChallenge($orm, null, Challenge::STATUS_SCHEDULED, $three_days_after, $six_days_after);
        $token1 = $orm->getRepository(TokenReward::class)->find(1);
        $this->createChallenge($orm, $token1, Challenge::STATUS_OPEN, $three_days_before, $three_days_after);
        $this->createChallenge($orm, null, Challenge::STATUS_CLOSED, $three_days_after, $six_days_after);
        $token2 = $orm->getRepository(TokenReward::class)->find(2);
        $this->createChallenge($orm, $token2, Challenge::STATUS_OPEN, $three_days_before, $three_days_after);


        $badge1 = $orm->getRepository(Badge::class)->find(1);
        $badge2 = $orm->getRepository(Badge::class)->find(2);

        $badges = [$badge1, $badge2];
        $token4 = $orm->getRepository(TokenReward::class)->find(4);
        $this->createChallenge($orm, $token4, Challenge::STATUS_OPEN, $three_days_before, $three_days_after, $badges);


        $token3 = $orm->getRepository(TokenReward::class)->find(3);
        $this->createChallenge($orm, $token3, Challenge::STATUS_OPEN, $three_days_before, $one_days_before);

    }

    /**
     * @param ObjectManager $orm
     */
    private function createChallenge(ObjectManager $orm, ?TokenReward $token, $status, $start, $finish, $badges = null){
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
        if($adminAccount){
            $challenge->setOwner($adminAccount);
        }

        if($badges){
            foreach ($badges as $badge){
                if($badge){
                    $challenge->addBadge($badge);
                }
            }
        }

        $orm->persist($challenge);

        if($token){
            $token->setChallenge($challenge);
        }
        $orm->flush();
    }

    public function getDependencies(){
        return [
            AccountFixture::class,
            TokenRewardsFixture::class,
            BadgesFixture::class
        ];
    }

}