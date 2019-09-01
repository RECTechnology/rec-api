<?php


namespace App\FinancialApiBundle\DataFixture;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\FinancialApiBundle\Entity\User;
use Faker\Factory;
use Faker\Generator;

class UserFixture extends Fixture {

    const TEST_USER_CREDENTIALS = ['username' => 'user_user', 'password' => 'user_user'];
    const TEST_ADMIN_CREDENTIALS = ['username' => 'admin_user', 'password' => 'admin_user'];

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();
        $user = $this->generateUser($faker, self::TEST_USER_CREDENTIALS['username']);
        $admin = $this->generateUser($faker, self::TEST_ADMIN_CREDENTIALS['username'], ['ROLE_SUPER_ADMIN']);
        $manager->persist($user);
        $manager->persist($admin);
        $manager->flush();
    }

    /**
     * @param Generator $faker
     * @param string $identifier
     * @param array $roles
     * @return User
     * @throws \Exception
     */
    protected function generateUser(Generator $faker, $identifier, array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setName($faker->name);
        $user->setUsername($identifier);
        $user->setEmail($faker->email);
        $user->setPlainPassword($identifier);
        $user->setPin($faker->shuffle('1234'));
        $user->setSecurityQuestion($faker->sentence);
        $user->setSecurityAnswer($faker->sentence);
        $user->setDNI($faker->shuffle('01234567') . 'A');
        $user->setPhone($faker->phoneNumber);
        $user->setPrefix('34');
        $user->setPublicPhone(true);
        $user->setEnabled(true);
        foreach ($roles as $role) {
            $user->addRole($role);
        }
        return $user;
    }
}