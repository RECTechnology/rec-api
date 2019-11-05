<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Controller\BaseApiV2Controller;
use App\FinancialApiBundle\Entity\KYC;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserGroup;
use App\FinancialApiBundle\Entity\UserWallet;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\FinancialApiBundle\Entity\Group as Account;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;

class AccountFixture extends Fixture implements DependentFixtureInterface {

    const ACCOUNT_TYPE_PRIVATE = 'PRIVATE';
    const ACCOUNT_TYPE_ORGANIZATION = 'COMPANY';
    const ACCOUNT_SUBTYPE_NORMAL = 'NORMAL';
    const ACCOUNT_SUBTYPE_BMINCOME = 'BMINCOME';
    const ACCOUNT_SUBTYPE_WHOLESALE = 'WHOLESALE';
    const ACCOUNT_SUBTYPE_RETAILER = 'RETAILER';

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        $faker = Factory::create();

        $user = $orm->getRepository(User::class)
            ->findOneBy(['username' => UserFixture::TEST_USER_CREDENTIALS['username']]);

        //This user has a private USER account, and a bmincomer account
        $this->createAccount($orm, $faker, $user);
        $this->createAccount(
            $orm,
            $faker,
            $user,
            [],
            self::ACCOUNT_TYPE_PRIVATE,
            self::ACCOUNT_SUBTYPE_BMINCOME,
            1
        );

        $admin = $orm->getRepository(User::class)
            ->findOneBy(['username' => UserFixture::TEST_ADMIN_CREDENTIALS['username']]);

        //This user has an ADMIN account, a RETAILER and WHOLESALE accounts
        $this->createAccount($orm, $faker, $admin, [BaseApiV2Controller::ROLE_SUPER_ADMIN]);
        $this->createAccount(
            $orm,
            $faker,
            $admin,
            [BaseApiV2Controller::ROLE_ORGANIZATION],
            self::ACCOUNT_TYPE_ORGANIZATION,
            self::ACCOUNT_SUBTYPE_RETAILER,
            2
        );
        $this->createAccount(
            $orm,
            $faker,
            $admin,
            [BaseApiV2Controller::ROLE_ORGANIZATION],
            self::ACCOUNT_TYPE_ORGANIZATION,
            self::ACCOUNT_SUBTYPE_WHOLESALE,
            2
        );


        $orm->flush();
    }

    /**
     * @param ObjectManager $orm
     * @param Generator $faker
     * @param User $user
     * @param array $roles
     * @param string $type
     * @param string $subtype
     * @param int $tier
     * @throws \Exception
     */
    private function createAccount(ObjectManager $orm, Generator $faker, User $user, array $roles = [], string $type = self::ACCOUNT_TYPE_PRIVATE, string $subtype = self::ACCOUNT_SUBTYPE_NORMAL, int $tier = 1){

        $account = new Account();
        $account->setName($faker->name);
        $account->setRecAddress($faker->shuffle('abcdefghijklmnopqrstuvwxyz0123456789'));
        $account->setMethodsList(['rec']);
        $account->setCif('B' . $faker->shuffle('01234567'));
        $account->setActive(true);
        $account->setEmail($user->getEmail());
        $account->setRoles($roles);
        $account->setKycManager($user);
        $account->setType($type);
        $account->setSubtype($subtype);
        $account->setTier($tier);

        $userAccount = new UserGroup();
        $userAccount->setGroup($account);
        $userAccount->setUser($user);
        $userAccount->setRoles(['ROLE_ADMIN']); //User is admin in the account

        if(!$user->getKycValidations()) {
            $kyc = new KYC();
            $kyc->setUser($user);
            $kyc->setName($user->getName());
            $kyc->setEmail($user->getEmail());
            $orm->persist($kyc);
        }
        $wallet = new UserWallet();
        $wallet->setCurrency('REC');
        $wallet->setAvailable(100e8);
        $wallet->setBalance(100e8);
        $wallet->setGroup($account);

        $orm->persist($wallet);
        $orm->persist($account);
        $orm->persist($user);
        $orm->persist($userAccount);
    }

    public function getDependencies(){
        return [
            UserFixture::class,
        ];
    }
}