<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Controller\BaseApiV2Controller;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\KYC;
use App\FinancialApiBundle\Entity\Tier;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserGroup;
use App\FinancialApiBundle\Entity\UserWallet;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\FinancialApiBundle\Entity\Group as Account;
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

        //This user has a private USER account, a bonissim account and a bmincomer account
        $this->createAccount(
            $orm,
            $faker,
            $user,
            [],
            self::ACCOUNT_TYPE_PRIVATE,
            self::ACCOUNT_SUBTYPE_NORMAL,
            1,
            $faker->name,
            1000e8
        );
        $this->createAccount(
            $orm,
            $faker,
            $user,
            [],
            self::ACCOUNT_TYPE_PRIVATE,
            self::ACCOUNT_SUBTYPE_BMINCOME,
            1,
            Campaign::BONISSIM_CAMPAIGN_NAME
        );
        $this->createAccount(
            $orm,
            $faker,
            $user,
            [],
            self::ACCOUNT_TYPE_PRIVATE,
            self::ACCOUNT_SUBTYPE_BMINCOME,
            1,
            $faker->name,
            1000e8
        );

        $admin = $orm->getRepository(User::class)
            ->findOneBy(['username' => UserFixture::TEST_ADMIN_CREDENTIALS['username']]);

        //This user has an ADMIN account, a RETAILER (bonissim) and WHOLESALE accounts
        $this->createAccount($orm, $faker, $admin, [BaseApiV2Controller::ROLE_SUPER_ADMIN]);
        $this->createAccount(
            $orm,
            $faker,
            $admin,
            [BaseApiV2Controller::ROLE_ORGANIZATION],
            self::ACCOUNT_TYPE_ORGANIZATION,
            self::ACCOUNT_SUBTYPE_RETAILER,
            2,
            Campaign::BONISSIM_CAMPAIGN_NAME
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
        $this->createAccount(
            $orm,
            $faker,
            $admin,
            [BaseApiV2Controller::ROLE_ORGANIZATION],
            self::ACCOUNT_TYPE_ORGANIZATION,
            self::ACCOUNT_SUBTYPE_WHOLESALE,
            2,
            "LTAB",
            100000e8
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
     * @param string $name
     * @param int $tier
     * @param float $balance
     * @throws \Exception
     */
    private function createAccount(ObjectManager $orm, Generator $faker, User $user, array $roles = [], string $type = self::ACCOUNT_TYPE_PRIVATE, string $subtype = self::ACCOUNT_SUBTYPE_NORMAL, int $tier = 1, string $name = null, float $balance=100e8){

        $account = new Account();

        if (isset($name)){
            $account->setName($name);
        }else{
            $account->setName($faker->name);
        }
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
        $account->setLatitude(1);
        $account->setLongitude(2);
        $level = $orm->getRepository(Tier::class)->findOneBy(['code' => Tier::KYC_LEVELS[1]]);
        $account->setLevel($level);

        $userAccount = new UserGroup();
        $userAccount->setGroup($account);
        $userAccount->setUser($user);
        $userAccount->setRoles(['ROLE_ADMIN']); //User is admin in the account

        if(!$user->getKycValidations()) {
            $kyc = new KYC();
            $kyc->setUser($user);
            $kyc->setName($user->getName());
            $kyc->setEmail($user->getEmail());
            $kyc->setPhone("678678678");
            $kyc->setPhoneValidated(true);
            $orm->persist($kyc);
        }
        $recWallet = new UserWallet();
        $recWallet->setCurrency('REC');
        $recWallet->setAvailable($balance);
        $recWallet->setBalance($balance);
        $recWallet->setGroup($account);

        $eurWallet = new UserWallet();
        $eurWallet->setCurrency('EUR');
        $eurWallet->setAvailable(0);
        $eurWallet->setBalance(0);
        $eurWallet->setGroup($account);

        $orm->persist($recWallet);
        $orm->persist($eurWallet);
        $orm->persist($account);
        $orm->persist($user);
        $orm->persist($userAccount);
    }

    public function getDependencies(){
        return [
            UserFixture::class,
            TierFixture::class
        ];
    }
}