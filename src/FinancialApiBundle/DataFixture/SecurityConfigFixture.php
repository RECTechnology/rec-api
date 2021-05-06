<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\UserSecurityConfig;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use PhpOption\None;

class SecurityConfigFixture extends Fixture implements DependentFixtureInterface {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        $this->createConfig($orm, 'pin', 5, null);
        $this->createConfig($orm, 'password', 5, null);
        $this->createConfig($orm, 'sms_validate_phone', 5, 86400);
        $this->createConfig($orm, 'sms_forget_password', 5, 86400);
        $this->createConfig($orm, 'sms_change_pin', 5, 86400);
        $this->createConfig($orm, 'sms_change_password', 5, 86400);

    }

    /**
     * @param ObjectManager $orm
     */
    private function createConfig(ObjectManager $orm, $type, $max_failures, $time_range){
        $config = new UserSecurityConfig();
        $config->setType($type);
        $config->setMaxFailures($max_failures);
        if($time_range){
            $config->setTimeRange($time_range);
        }
        $orm->persist($config);
        $orm->flush();
    }

    public function getDependencies(){
        return [
            UserFixture::class,
        ];
    }
}