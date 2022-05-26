<?php

namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\Award;
use App\FinancialApiBundle\Entity\Badge;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class AwardsFixture extends Fixture
{

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        // TODO: Implement load() method.
        $this->createAward($orm, 'La saviesa');
        $this->createAward($orm, 'La paraula');
    }

    /**
     * @param ObjectManager $orm
     */
    private function createAward(ObjectManager $orm, $name){

        $award = new Award();
        $award->setName($name);
        $award->setNameCa($name.'_cat');
        $award->setNameEs($name.'_es');
        $award->setBronzeThreshold(10);
        $award->setSilverThreshold(50);
        $award->setGoldenThreshold(100);

        $orm->persist($award);
        $orm->flush();
    }

}