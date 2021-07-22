<?php

namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\DocumentKind;
use App\FinancialApiBundle\Entity\Tier;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class DocumentKindFixture extends Fixture implements DependentFixtureInterface {

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        $tiers = $manager->getRepository(Tier::class)->findAll();

        /** @var Tier $tier */
        foreach ($tiers as $tier){
            $documentKind = new DocumentKind();
            $documentKind->setName('dni');
            $documentKind->setDescription($faker->text);
            $documentKind->setIsUserDocument(true);

            $manager->persist($documentKind);
            $manager->flush();
        }

    }

    public function getDependencies(){
        return [
            TierFixture::class
        ];
    }
}