<?php


namespace App\DataFixtures;

use App\Controller\BaseApiV2Controller;
use App\Entity\Campaign;
use App\Entity\KYC;
use App\Entity\Tier;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\UsersSmsLogs;
use App\Entity\UserWallet;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Group as Account;
use Faker\Factory;
use Faker\Generator;

class UsersSmsLogsFixtures extends Fixture implements DependentFixtureInterface {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {

        $user = $orm->getRepository(User::class)
            ->findOneBy(['username' => UserFixtures::TEST_USER_LOCKED_CREDENTIALS['username']]);

        $sms = new UsersSmsLogs();
        $sms->setUserId($user->getId());
        $sms->setType('sms_unlock_user');
        $sms->setSecurityCode($user->getLastSmsCode());

        $orm->persist($sms);
        $orm->flush();

    }

    public function getDependencies(){
        return [
            UserFixtures::class
        ];
    }
}