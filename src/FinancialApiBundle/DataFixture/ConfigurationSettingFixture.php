<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\ConfigurationSetting;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class ConfigurationSettingFixture extends Fixture {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        $this->_createSetting($orm, ConfigurationSetting::SHOP_BADGES_SCOPE, 'threshold', '0.5');
        $this->_createSetting($orm, ConfigurationSetting::SHOP_BADGES_SCOPE, 'max_qualifications', '10');
        $this->_createSetting($orm, ConfigurationSetting::SHOP_BADGES_SCOPE, 'min_qualifications', '0');
        $this->_createSetting($orm, ConfigurationSetting::APP_SCOPE, 'badges_filter', 'disabled');
        $this->_createSetting($orm, ConfigurationSetting::QUALIFICATIONS_SCOPE, ConfigurationSetting::SETTING_QUALIFICATIONS_SYSTEM_STATUS, 'enabled');

    }

    private function _createSetting(ObjectManager $orm, $scope, $name, $value){
        $config = new ConfigurationSetting();
        $config->setScope($scope);
        $config->setName($name);
        $config->setValue($value);

        $orm->persist($config);
        $orm->flush();
    }

}