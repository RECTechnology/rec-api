<?php

namespace App\DataFixtures;

use App\Entity\Badge;
use App\Entity\Challenge;
use App\Entity\TokenReward;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class TokenRewardsFixtures extends Fixture
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
        $this->createTokenReward($orm, TokenReward::STATUS_CREATED);

        $this->createTokenReward($orm, TokenReward::STATUS_MINTED);
        $this->createTokenReward($orm, TokenReward::STATUS_CREATED);
        $this->createTokenReward($orm, TokenReward::STATUS_CREATED);
        $this->createTokenReward($orm, TokenReward::STATUS_CREATED);
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