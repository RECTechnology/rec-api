<?php


namespace App\FinancialApiBundle\DataFixture;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\FinancialApiBundle\Entity\User;
use Faker\Factory;

class UserFixture extends Fixture {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();
        $user = new User();
        $user->setName($faker->name);
        $user->setUsername($faker->userName);
        $user->setEmail($faker->email);
        $user->setPlainPassword($faker->password);
        $user->setPin($faker->shuffle('1234'));
        $user->setSecurityQuestion($faker->sentence);
        $user->setSecurityAnswer($faker->sentence);
        $user->setDNI($faker->shuffle('01234567') . 'A');
        $user->setPhone($faker->phoneNumber);
        $user->setPrefix('34');
        $user->setPublicPhone(true);
        $manager->persist($user);
        $manager->flush();
    }
}