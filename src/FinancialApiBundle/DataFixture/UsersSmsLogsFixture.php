<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Controller\BaseApiV2Controller;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\KYC;
use App\FinancialApiBundle\Entity\Tier;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserGroup;
use App\FinancialApiBundle\Entity\UsersSmsLogs;
use App\FinancialApiBundle\Entity\UserWallet;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\FinancialApiBundle\Entity\Group as Account;
use Faker\Factory;
use Faker\Generator;

class UsersSmsLogsFixture extends Fixture implements DependentFixtureInterface {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {

        $user = $orm->getRepository(User::class)
            ->findOneBy(['username' => UserFixture::TEST_USER_LOCKED_CREDENTIALS['username']]);

        $sms = new UsersSmsLogs();
        $sms->setUserId($user->getId());
        $sms->setType('sms_unlock_user');
        $sms->setSecurityCode($user->getLastSmsCode());

        $orm->persist($sms);
        $orm->flush();

    }

    public function getDependencies(){
        return [
            UserFixture::class
        ];
    }
}