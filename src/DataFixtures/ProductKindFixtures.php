<?php


namespace App\DataFixtures;

use App\Entity\Activity;
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
            $this->createProductKind($orm, $faker->name, $faker->name, $faker->name, $faker->text, ProductKind::STATUS_REVIEWED);
        }
        $this->createProductKind($orm, $faker->name, '', '', $faker->text, ProductKind::STATUS_CREATED);

        $this->createProductKind($orm, 'banana', 'platano', 'platan', $faker->text, ProductKind::STATUS_REVIEWED);
        $this->createProductKind($orm, 'Mussel', 'Mejillón', 'Musclo', $faker->text, ProductKind::STATUS_REVIEWED);
        $this->createProductKind($orm, 'Pasta retailer', 'Pasta al menor', 'Pasta al menor', $faker->text, ProductKind::STATUS_CREATED);
    }

    /**
     * @param ObjectManager $orm
     */
    private function createProductKind(ObjectManager $orm, $name, $name_es, $name_cat, $description, $status){
        $product = new ProductKind();
        $product->setStatus($status);
        $product->setDescription($description);
        $product->setDescriptionEs($description);
        $product->setDescriptionCa($description);
        $product->setName($name);
        $product->setNameCa($name_cat);
        $product->setNameEs($name_es);

        //Get activity
        /** @var Activity $activity */
        $activity = $orm->getRepository(Activity::class)->findall();
        $activity_selected = $activity[0];

        //Add activity in product
        $product->addActivity($activity_selected);

        $orm->persist($product);
        $orm->flush();
    }


}