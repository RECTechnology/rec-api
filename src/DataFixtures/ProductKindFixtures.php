<?php


namespace App\DataFixtures;

use App\Entity\ProductKind;
use App\Entity\Tier;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class ProductKindFixtures extends Fixture {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        $faker = Factory::create();
        for ($i = 0 ; $i < 10; $i++){
            $this->createProductKind($orm, $faker->name, $faker->text, ProductKind::STATUS_REVIEWED);
        }
        $this->createProductKind($orm, $faker->name, $faker->text, ProductKind::STATUS_CREATED);
        $this->createProductKind($orm, 'banana', $faker->text, ProductKind::STATUS_REVIEWED);
        $this->createProductKind($orm, 'banco', $faker->text, ProductKind::STATUS_CREATED);
    }

    /**
     * @param ObjectManager $orm
     */
    private function createProductKind(ObjectManager $orm, $name, $description, $status){
        $product = new ProductKind();
        $product->setStatus($status);
        $product->setDescription($description);
        $product->setDescriptionEs($description);
        $product->setDescriptionCa($description);
        $product->setName($name);
        $product->setNameCa($name);
        $product->setNameEs($name);

        $orm->persist($product);
        $orm->flush();
    }


}