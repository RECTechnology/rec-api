<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Controller\Google2FA;
use App\FinancialApiBundle\Entity\UsersSmsLogs;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\FinancialApiBundle\Entity\User;
use Faker\Factory;
use Faker\Generator;

class UserFixture extends Fixture {

    const TEST_USER_CREDENTIALS = ['name' => 'paco', 'username' => '01234567l', 'password' => 'user_user', 'pin' => '0123'];
    const TEST_ADMIN_CREDENTIALS = ['name' => 'ADMINUSER', 'username' => '69816559J', 'password' => 'admin_user', 'pin' => '3210'];
    const TEST_USER_LOCKED_CREDENTIALS = ['name' => 'USERLOCKED', 'username' => '89706253C', 'password' => 'user_locked', 'pin' => '1230'];
    const TEST_USER_PHONE_NON_VALIDATED = ['name' => 'USERPHONENONVALIDATED', 'username' => '62477324D', 'password' => 'user_phone_non_validated', 'pin' => '2301'];
    const TEST_USER_LTAB_CREDENTIALS = ['name' => 'USERLTAB', 'username' => '95145408C', 'password' => 'user_LTAB', 'pin' => '3012'];
    const TEST_USER_LTAB_COMMERCE_CREDENTIALS = ['name' => 'USERLTABCOMMERCE', 'username' => '24670133B', 'password' => 'user_LTAB_commerce', 'pin' => '0012'];
    const TEST_SECOND_USER_CREDENTIALS = ['name' => 'paco', 'username' => '01234567B', 'password' => 'user_user', 'pin' => '0123'];

    const TEST_POS_MANAGER_CREDENTIALS = ['name' => 'pos', 'username' => '01234467B', 'password' => 'manager_pos', 'pin' => '1223'];

    const TEST_THIRD_USER_CREDENTIALS = ['name' => 'vicent', 'username' => '01234567C', 'password' => 'user_user', 'pin' => '1234'];


    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();
        $admin = $this->generateUser($faker, self::TEST_ADMIN_CREDENTIALS);
        $user = $this->generateUser($faker, self::TEST_USER_CREDENTIALS);
        $user_locked = $this->generateUser($faker, self::TEST_USER_LOCKED_CREDENTIALS);
        $user_phone_non_validated = $this->generateUser($faker, self::TEST_USER_PHONE_NON_VALIDATED);
        $user_ltab = $this->generateUser($faker, self::TEST_USER_LTAB_CREDENTIALS);
        $user_ltab_commerce = $this->generateUser($faker, self::TEST_USER_LTAB_COMMERCE_CREDENTIALS);
        $user_second_user = $this->generateUser($faker, self::TEST_SECOND_USER_CREDENTIALS);

        $manager_pos = $this->generateUser($faker, self::TEST_POS_MANAGER_CREDENTIALS);

        $user_third_user = $this->generateUser($faker, self::TEST_THIRD_USER_CREDENTIALS);

        $manager->persist($admin);
        $manager->persist($user);
        $manager->persist($user_locked);
        $manager->persist($user_phone_non_validated);
        $manager->persist($user_ltab);
        $manager->persist($user_ltab_commerce);
        $manager->persist($user_second_user);

        $manager->persist($manager_pos);

        $manager->persist($user_third_user);

        $manager->flush();
    }

    /**
     * @param Generator $faker
     * @param $credentials
     * @return User
     * @throws \Exception
     */
    protected function generateUser(Generator $faker, $credentials): User
    {
        $user = new User();
        $user->setName($credentials['name']);
        $user->setUsername($credentials['username']);
        $user->setEmail($faker->email);
        $user->setPlainPassword($credentials['password']);
        $user->setPin($credentials['pin']);
        $user->setSecurityQuestion($faker->sentence);
        $user->setSecurityAnswer($faker->sentence);
        if ($credentials["username"] == "01234567l"){
            $user->setDNI('01234567l');
            $user->setPrefix(34);
            $user->setPhone(789789789);
        }else{
            $user->setDNI($faker->shuffle('01234567') . 'A');
            $user->setPhone(random_int(600000000, 799999999));
        }
        $user->setPrefix('34');
        $user->setPublicPhone(true);
        $user->setEnabled(true);
        $user->setLocale('es');
        $user->setTwoFactorAuthentication(true);
        $user->setTwoFactorCode(Google2FA::generate_secret_key());
        if($credentials['name'] == "USERLOCKED"){
            $code = strval(random_int(100000, 999999));
            $user->lockUser();
            $user->setEnabled(false);
            $user->setPasswordFailures(5);
            $user->setLastSmscode($code);
        }
        if($credentials['name'] == "USERPHONENONVALIDATED"){
            $user->setEnabled(false);
        }
        return $user;
    }
}