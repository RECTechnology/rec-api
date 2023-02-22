<?php


namespace App\DataFixtures;

use App\Entity\ConfigurationSetting;
use App\Entity\Package;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class ConfigurationSettingFixtures extends Fixture implements DependentFixtureInterface {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        /** @var Package $b2b_atarca */
        $b2b_atarca = $orm->getRepository(Package::class)->findOneBy(array('name' => 'b2b_atarca'));
        $this->_createSetting($orm, ConfigurationSetting::ADMIN_PANEL_SCOPE, 'menu_item_b2b', 'enabled', 'boolean', 'admin_panel', $b2b_atarca);

        /** @var Package $bulk_mailing */
        $bulk_mailing = $orm->getRepository(Package::class)->findOneBy(array('name' => 'bulk_mailing'));
        $this->_createSetting($orm, ConfigurationSetting::ADMIN_PANEL_SCOPE, 'menu_item_email', 'enabled', 'boolean', 'admin_panel', $bulk_mailing);

        /** @var Package $badges */
        $badges = $orm->getRepository(Package::class)->findOneBy(array('name' => 'badges'));
        $this->_createSetting($orm, ConfigurationSetting::ADMIN_PANEL_SCOPE, 'menu_item_qualifications', 'enabled', 'boolean', 'admin_panel', $badges);
        $this->_createSetting($orm, ConfigurationSetting::SHOP_BADGES_SCOPE, 'max_qualifications', '10', 'int', 'api', $badges);
        $this->_createSetting($orm, ConfigurationSetting::SHOP_BADGES_SCOPE, 'min_qualifications', '0', 'int', 'api', $badges);
        $this->_createSetting($orm, ConfigurationSetting::SHOP_BADGES_SCOPE, 'threshold', '0.5', 'double', 'api', $badges);
        $this->_createSetting($orm, ConfigurationSetting::APP_SCOPE, 'map_badges_filter_status', 'enabled', 'boolean', 'app', $badges);
        $this->_createSetting($orm, ConfigurationSetting::APP_SCOPE, 'profile_pis_status', 'enabled', 'boolean', 'senfake', $badges);

        /** @var Package $reports */
        $reports = $orm->getRepository(Package::class)->findOneBy(array('name' => 'reports'));
        $this->_createSetting($orm, ConfigurationSetting::ADMIN_PANEL_SCOPE, 'menu_item_reports', 'enabled', 'boolean', 'admin_panel', $reports);

        /** @var Package $challenges */
        $challenges = $orm->getRepository(Package::class)->findOneBy(array('name' => 'challenges'));
        $this->_createSetting($orm, ConfigurationSetting::NFT_SCOPE, 'c2b_challenges_status', 'enabled', 'boolean', 'api', $challenges);
        $this->_createSetting($orm, ConfigurationSetting::APP_SCOPE, 'c2b_challenges_status', 'enabled', 'boolean', 'app', $challenges);

        /** @var Package $nft_wallet */
        $nft_wallet = $orm->getRepository(Package::class)->findOneBy(array('name' => 'nft_wallet'));
        $this->_createSetting($orm, ConfigurationSetting::NFT_SCOPE, 'create_nft_wallet', 'enabled', 'boolean', 'api', $nft_wallet);
        $this->_createSetting($orm, ConfigurationSetting::NFT_SCOPE, 'default_funding_amount', '10000000000000000', 'int', 'api', $nft_wallet);

        /** @var Package $qualifications */
        $qualifications = $orm->getRepository(Package::class)->findOneBy(array('name' => 'qualifications'));
        $this->_createSetting($orm, ConfigurationSetting::QUALIFICATIONS_SCOPE, 'qualifications_system_status', 'enabled', 'boolean', 'app', $qualifications);
    }

    private function _createSetting(ObjectManager $orm, $scope, $name, $value, $type, $platform, Package $package){
        $config = new ConfigurationSetting();
        $config->setScope($scope);
        $config->setName($name);
        $config->setValue($value);
        $config->setType($type);
        $config->setPlatform($platform);
        $config->setPackage($package);

        $orm->persist($config);
        $orm->flush();
    }

    public function getDependencies()
    {
        return [
            PackageFixtures::class
        ];
    }
}