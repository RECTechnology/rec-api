<?php

namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\Badge;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class BadgesFixture extends Fixture
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
        for ($i =0;$i<10; $i++){
            $this->createBadge($orm);
        }
    }

    /**
     * @param ObjectManager $orm
     */
    private function createBadge(ObjectManager $orm){
        $faker = Factory::create();
        $badge = new Badge();
        $name = $faker->name;
        $badge->setName($name);
        $badge->setNameEs($name.'_es');
        $badge->setNameCa($name.'_cat');
        $description = $faker->text;
        $badge->setDescription($description);
        $badge->setDescriptionEs($description.'_es');
        $badge->setDescriptionCa($description.'_cat');
        $badge->setEnabled(true);

        $orm->persist($badge);
        $orm->flush();
    }

}