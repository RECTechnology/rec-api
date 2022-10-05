<?php

namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\Badge;
use App\FinancialApiBundle\Entity\Challenge;
use App\FinancialApiBundle\Entity\TokenReward;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class TokenRewardsFixture extends Fixture
{

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        $this->createTokenReward($orm, TokenReward::STATUS_CREATED);
        $this->createTokenReward($orm, TokenReward::STATUS_MINTED);
    }

    /**
     * @param ObjectManager $orm
     */
    private function createTokenReward(ObjectManager $orm, $status){
        $faker = Factory::create();

        $token = new TokenReward();
        $token->setName($faker->name);
        $token->setDescription($faker->text);
        $token->setStatus($status);
        $token->setImage('https://fakeimage.com/images/1.jpg');

        $orm->persist($token);
        $orm->flush();
    }

}