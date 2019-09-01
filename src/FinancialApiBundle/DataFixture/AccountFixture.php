<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\KYC;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserGroup;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\FinancialApiBundle\Entity\Group as Account;
use Faker\Factory;

class AccountFixture extends Fixture implements DependentFixtureInterface {

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
        $account->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ADMIN']);

        $users = $manager->getRepository(User::class)->findAll();
        $account->setKycManager($users[0]);

        /** @var User $user */
        foreach ($users as $user) {
            $userGroup = new UserGroup();
            $userGroup->setGroup($account);
            $userGroup->setUser($user);
            $userGroup->setRoles(['ROLE_ADMIN']);

            $kyc = new KYC();
            $kyc->setUser($user);
            $kyc->setName($user->getName());
            $kyc->setEmail($user->getEmail());

            $manager->persist($kyc);
            $manager->persist($account);
            $manager->persist($user);
            $manager->persist($userGroup);
        }
        $manager->flush();
    }

    public function getDependencies(){
        return [
            UserFixture::class,
        ];
    }
}