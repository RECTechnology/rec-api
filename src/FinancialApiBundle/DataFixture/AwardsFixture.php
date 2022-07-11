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
        $this->createAward($orm, 'La impuls');
    }

    /**
     * @param ObjectManager $orm
     */
    private function createAward(ObjectManager $orm, $name){

        $award = new Award();
        $award->setName($name);
        $award->setNameCa($name.'_cat');
        $award->setNameEs($name.'_es');
        $award->setThresholds(array(10,100,1000,10000));

        $orm->persist($award);
        $orm->flush();
    }

}