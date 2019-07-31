<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\FinancialApiBundle\Entity\Group as Account;
use Faker\Factory;

class AccountFixture extends Fixture {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();
        $account = new Account();
        $account->setName($faker->name);
        $account->setRecAddress($faker->shuffle('abcdefghijklmnopqrstuvwxyz0123456789'));
        $account->setMethodsList(['rec']);
        $account->setCif('B' . $faker->shuffle('01234567'));
        $account->setActive(true);

        /** @var User $user */
        $user = $manager->getRepository(User::class)->findOneBy(['username' => 'user_user']);
        $account->setKycManager($user);
        $manager->persist($account);
        $manager->flush();
    }

    public function getDependencies(){
        return [
            UserFixture::class,
        ];
    }
}