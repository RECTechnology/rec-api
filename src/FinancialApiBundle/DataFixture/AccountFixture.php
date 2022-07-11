<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Controller\BaseApiV2Controller;
use App\FinancialApiBundle\Entity\Activity;
use App\FinancialApiBundle\Entity\Badge;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\Group;
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

    const TEST_ACCOUNT_LTAB_COMMERCE = ['name' => 'account_org_in_ltab'];
    const TEST_ACCOUNT_CULT21_COMMERCE = ['name' => 'account_org_in_cult21'];
    const TEST_ACCOUNT_LTAB_PRIVATE = ['name' => 'account_in_ltab'];
    const TEST_ACCOUNT_COMMERCE = ['name' => 'COMMERCEACCOUNT'];
    const TEST_ACCOUNT_COMMERCE_POS = ['name' => 'COMMERCEACCOUNT_POS'];
    const TEST_ACCOUNT_REZERO_1 = ['name' => 'REZERO_1'];
    const TEST_ACCOUNT_REZERO_2 = ['name' => 'REZERO_2'];
    const TEST_ACCOUNT_REZERO_3 = ['name' => 'REZERO_3'];
    const TEST_SHOP_ACCOUNT = ['name' => 'Shop'];


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

        $worker_user = $orm->getRepository(User::class)
            ->findOneBy(['username' => UserFixture::TEST_SECOND_USER_CREDENTIALS['username']]);

        $third_user = $orm->getRepository(User::class)
            ->findOneBy(['username' => UserFixture::TEST_THIRD_USER_CREDENTIALS['username']]);

        $user_in_shop = $orm->getRepository(User::class)
            ->findOneBy(['username' => UserFixture::TEST_USER_IN_SHOP['username']]);

        //This user has a private USER account, a bonissim account and a bmincomer account
        $this->createAccount(
            $orm,
            $faker,
            $user,
            [],
            self::ACCOUNT_TYPE_PRIVATE,
            self::ACCOUNT_SUBTYPE_NORMAL,
            1,
            'duplicated_name',
            1000e8,
            $worker_user
        );
        $this->createAccount(
            $orm,
            $faker,
            $worker_user,
            [],
            self::ACCOUNT_TYPE_ORGANIZATION,
            self::ACCOUNT_SUBTYPE_RETAILER,
            1,
            'duplicated_name',
            1000e8
        );
        $this->createAccount(
            $orm,
            $faker,
            $third_user,
            [],
            self::ACCOUNT_TYPE_PRIVATE,
            self::ACCOUNT_SUBTYPE_NORMAL,
            1,
            UserFixture::TEST_THIRD_USER_CREDENTIALS['name'],
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
            'private_account_1',
            1000e8
        );

        $this->createAccount(
            $orm,
            $faker,
            $user_in_shop,
            [],
            self::ACCOUNT_TYPE_ORGANIZATION,
            self::ACCOUNT_SUBTYPE_RETAILER,
            1,
            self::TEST_SHOP_ACCOUNT['name'],
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
            2,
            self::TEST_ACCOUNT_COMMERCE['name']
        );

        //This user has a private USER account and is locked by password failures
        $user_locked = $orm->getRepository(User::class)
            ->findOneBy(['username' => UserFixture::TEST_USER_LOCKED_CREDENTIALS['username']]);
        $this->createAccount(
            $orm,
            $faker,
            $user_locked,
            [],
            self::ACCOUNT_TYPE_PRIVATE,
            self::ACCOUNT_SUBTYPE_NORMAL,
            1,
            $faker->name,
            1000e8
        );

        //This user has a private USER account and is disabled because phone is not validated
        $user_phone_non_validated = $orm->getRepository(User::class)
            ->findOneBy(['username' => UserFixture::TEST_USER_PHONE_NON_VALIDATED['username']]);
        $this->createAccount(
            $orm,
            $faker,
            $user_phone_non_validated,
            [],
            self::ACCOUNT_TYPE_PRIVATE,
            self::ACCOUNT_SUBTYPE_NORMAL,
            1,
            $faker->name,
            1000e8
        );

        $user_LTAB = $orm->getRepository(User::class)
            ->findOneBy(['username' => UserFixture::TEST_USER_LTAB_CREDENTIALS['username']]);
        $this->createAccount(
            $orm,
            $faker,
            $user_LTAB,
            [],
            self::ACCOUNT_TYPE_PRIVATE,
            self::ACCOUNT_SUBTYPE_NORMAL,
            2,
            self::TEST_ACCOUNT_LTAB_PRIVATE['name'].'_private',
            1000e8
        );

        $this->createAccount(
            $orm,
            $faker,
            $user_LTAB,
            [],
            self::ACCOUNT_TYPE_PRIVATE,
            self::ACCOUNT_SUBTYPE_NORMAL,
            2,
            Campaign::BONISSIM_CAMPAIGN_NAME,
            1000e8
        );

        $this->createAccount(
            $orm,
            $faker,
            $user_LTAB,
            [],
            self::ACCOUNT_TYPE_PRIVATE,
            self::ACCOUNT_SUBTYPE_NORMAL,
            2,
            Campaign::CULTURE_CAMPAIGN_NAME,
            1000e8
        );

        $this->createAccount(
            $orm,
            $faker,
            $user_LTAB,
            [BaseApiV2Controller::ROLE_ORGANIZATION],
            self::ACCOUNT_TYPE_ORGANIZATION,
            self::ACCOUNT_SUBTYPE_RETAILER,
            2,
            self::TEST_ACCOUNT_LTAB_PRIVATE['name'].'_store',
            1000e8
        );

        $user_LTAB_org = $orm->getRepository(User::class)
            ->findOneBy(['username' => UserFixture::TEST_USER_LTAB_COMMERCE_CREDENTIALS['username']]);
        $this->createAccount(
            $orm,
            $faker,
            $user_LTAB_org,
            [BaseApiV2Controller::ROLE_ORGANIZATION],
            self::ACCOUNT_TYPE_ORGANIZATION,
            self::ACCOUNT_SUBTYPE_RETAILER,
            2,
            self::TEST_ACCOUNT_LTAB_COMMERCE['name'],
            1000e8
        );

        $this->createAccount(
            $orm,
            $faker,
            $admin,
            [BaseApiV2Controller::ROLE_ORGANIZATION],
            self::ACCOUNT_TYPE_ORGANIZATION,
            self::ACCOUNT_SUBTYPE_RETAILER,
            2,
            self::TEST_ACCOUNT_CULT21_COMMERCE['name'],
            1000e8
        );

        $this->createAccount(
            $orm,
            $faker,
            $admin,
            [BaseApiV2Controller::ROLE_ORGANIZATION],
            self::ACCOUNT_TYPE_ORGANIZATION,
            self::ACCOUNT_SUBTYPE_WHOLESALE,
            2,
            "CULT21",
            100000e8
        );

        $manager_pos_org = $orm->getRepository(User::class)
            ->findOneBy(['username' => UserFixture::TEST_POS_MANAGER_CREDENTIALS['username']]);
        $this->createAccount(
            $orm,
            $faker,
            $manager_pos_org,
            [BaseApiV2Controller::ROLE_ORGANIZATION],
            self::ACCOUNT_TYPE_ORGANIZATION,
            self::ACCOUNT_SUBTYPE_WHOLESALE,
            2,
            "POS COMMERCE",
            100000e8
        );

        $rezero_org_1 = $orm->getRepository(User::class)
            ->findOneBy(['username' => UserFixture::TEST_REZERO_USER_1_CREDENTIALS['username']]);
        $this->createAccount(
            $orm,
            $faker,
            $rezero_org_1,
            [BaseApiV2Controller::ROLE_ORGANIZATION],
            self::ACCOUNT_TYPE_ORGANIZATION,
            self::ACCOUNT_SUBTYPE_WHOLESALE,
            2,
            self::TEST_ACCOUNT_REZERO_1['name'],
            100000e8
        );

        $rezero_org_2 = $orm->getRepository(User::class)
            ->findOneBy(['username' => UserFixture::TEST_REZERO_USER_2_CREDENTIALS['username']]);
        $this->createAccount(
            $orm,
            $faker,
            $rezero_org_2,
            [BaseApiV2Controller::ROLE_ORGANIZATION],
            self::ACCOUNT_TYPE_ORGANIZATION,
            self::ACCOUNT_SUBTYPE_WHOLESALE,
            2,
            self::TEST_ACCOUNT_REZERO_2['name'],
            100000e8
        );

        $rezero_org_3 = $orm->getRepository(User::class)
            ->findOneBy(['username' => UserFixture::TEST_REZERO_USER_3_CREDENTIALS['username']]);
        $this->createAccount(
            $orm,
            $faker,
            $rezero_org_3,
            [BaseApiV2Controller::ROLE_ORGANIZATION],
            self::ACCOUNT_TYPE_ORGANIZATION,
            self::ACCOUNT_SUBTYPE_WHOLESALE,
            2,
            self::TEST_ACCOUNT_REZERO_3['name'],
            100000e8
        );

        //This one has to be the last one because some tests like #testCountryNotValid are expecting this
        //because of use admin to retrieve all accounts and gets the first on and needs to be part of this account
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
    private function createAccount(ObjectManager $orm, Generator $faker, User $user, array $roles = [], string $type = self::ACCOUNT_TYPE_PRIVATE, string $subtype = self::ACCOUNT_SUBTYPE_NORMAL, int $tier = 1, string $name = null, float $balance=100e8, User $worker_user=null){

        $account = new Account();

        if (isset($name)){
            $account->setName($name);

        }else{
            $account->setName($faker->name);
        }
        if ($type == self::ACCOUNT_TYPE_ORGANIZATION) {
            $activity = $orm->getRepository(Activity::class)->find(['id' => $tier]);
            $account->setActivityMain($activity);
            $account->addActivity($activity);
            $badge = $orm->getRepository(Badge::class)->find(['id' => 0]);
            //$account->addBadge($badge);
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
        $level = $orm->getRepository(Tier::class)->findOneBy(['code' => Tier::KYC_LEVELS[$tier]]);
        $account->setLevel($level);

        if($name === self::TEST_ACCOUNT_REZERO_2['name']){
            $account->setRezeroB2bApiKey("7-47f3b325834998029ee869518a00e7e4f9952aea9eac40fabae57e2a4837e50");
            $account->setRezeroB2bUserId(40);
            $account->setRezeroB2bUsername("anbton");
            $account->setRezeroB2bAccess(Group::ACCESS_STATE_GRANTED);

            $activity = $orm->getRepository(Activity::class)->findOneBy(['name' => Activity::GREEN_COMMERCE_ACTIVITY]);
            $account->addActivity($activity);
            $badge = $orm->getRepository(Badge::class)->findOneBy(['name' => "Test"]);
            $account->addBadge($badge);
        }

        if($name === self::TEST_ACCOUNT_REZERO_3['name']){
            $account->setRezeroB2bApiKey("747f3b325834998029ee869518a00e7e4f9952aea9eac40fabae57e2a4837e50");
            $account->setRezeroB2bUserId(41);
            $account->setRezeroB2bUsername(UserFixture::TEST_REZERO_USER_3_CREDENTIALS['name']);
            $account->setRezeroB2bAccess(Group::ACCESS_STATE_GRANTED);

            $activity = $orm->getRepository(Activity::class)->findOneBy(['name' => Activity::GREEN_COMMERCE_ACTIVITY]);
            $account->addActivity($activity);
            $badge = $orm->getRepository(Badge::class)->findOneBy(['name' => "Test"]);
            $account->addBadge($badge);
        }

        $userAccount = new UserGroup();
        $userAccount->setGroup($account);
        $userAccount->setUser($user);
        //TODO: This must not be hard coded, it should be dinamic depending the org
        $userAccount->setRoles(['ROLE_ADMIN']); //User is admin in the account

        if($worker_user){
            $workerAccount = new UserGroup();
            $workerAccount->setGroup($account);
            $workerAccount->setUser($worker_user);
            $workerAccount->setRoles(['ROLE_WORKER']);
            $worker_user->setActiveGroup($account);
        }

        if(!$user->getKycValidations()) {
            $kyc = new KYC();
            $kyc->setUser($user);
            $kyc->setName($user->getName());
            $kyc->setEmail($user->getEmail());
            $kyc->setPhone("678678678");
            if($user->getName() == 'USERPHONENONVALIDATED'){
                $kyc->setPhoneValidated(false);
            }else{
                $kyc->setPhoneValidated(true);
            }
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