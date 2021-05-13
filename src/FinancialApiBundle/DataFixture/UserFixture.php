<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Controller\Google2FA;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\FinancialApiBundle\Entity\User;
use Faker\Factory;
use Faker\Generator;

class UserFixture extends Fixture {

    const TEST_USER_CREDENTIALS = ['username' => '01234567A', 'password' => 'user_user', 'pin' => '0123'];
    const TEST_ADMIN_CREDENTIALS = ['username' => 'admin_user', 'password' => 'admin_user', 'pin' => '3210'];

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
        $manager->persist($admin);
        $manager->persist($user);
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
        $user->setName($faker->name);
        $user->setUsername($credentials['username']);
        $user->setEmail($faker->email);
        $user->setPlainPassword($credentials['password']);
        $user->setPin($credentials['pin']);
        $user->setSecurityQuestion($faker->sentence);
        $user->setSecurityAnswer($faker->sentence);
        if ($credentials["username"] == "01234567A"){
            $user->setDNI('01234567A');
            $user->setPrefix(34);
            $user->setPhone(789789789);
        }else{
            $user->setDNI($faker->shuffle('01234567') . 'A');
            $user->setPhone($faker->phoneNumber);
        }
        $user->setPrefix('34');
        $user->setPublicPhone(true);
        $user->setEnabled(true);
        $user->setLocale('es');
        $user->setTwoFactorAuthentication(true);
        $user->setTwoFactorCode(Google2FA::generate_secret_key());
        return $user;
    }
}