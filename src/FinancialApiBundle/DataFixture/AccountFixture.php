<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Controller\BaseApiV2Controller;
use App\FinancialApiBundle\Entity\KYC;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserGroup;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\FinancialApiBundle\Entity\Group as Account;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;

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

        $user = $manager
            ->getRepository(User::class)
            ->findOneBy(['username' => UserFixture::TEST_USER_CREDENTIALS['username']]);

        $this->createAccount($manager, $faker, $user, [BaseApiV2Controller::ROLE_USER]);

        $admin = $manager
            ->getRepository(User::class)
            ->findOneBy(['username' => UserFixture::TEST_ADMIN_CREDENTIALS['username']]);

        $this->createAccount($manager, $faker, $admin, [BaseApiV2Controller::ROLE_SUPER_ADMIN]);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param Generator $faker
     * @param User $user
     * @param array $roles
     * @throws \Exception
     */
    private function createAccount(ObjectManager $manager, Generator $faker, User $user, array $roles){

        $account = new Account();
        $account->setName($faker->name);
        $account->setRecAddress($faker->shuffle('abcdefghijklmnopqrstuvwxyz0123456789'));
        $account->setMethodsList(['rec']);
        $account->setCif('B' . $faker->shuffle('01234567'));
        $account->setActive(true);
        $account->setRoles($roles);
        $account->setKycManager($user);

        $userGroup = new UserGroup();
        $userGroup->setGroup($account);
        $userGroup->setUser($user);
        $userGroup->setRoles(['ROLE_ADMIN']); //User is admin in the account

        $kyc = new KYC();
        $kyc->setUser($user);
        $kyc->setName($user->getName());
        $kyc->setEmail($user->getEmail());

        $manager->persist($kyc);
        $manager->persist($account);
        $manager->persist($user);
        $manager->persist($userGroup);
    }

    public function getDependencies(){
        return [
            UserFixture::class,
        ];
    }
}