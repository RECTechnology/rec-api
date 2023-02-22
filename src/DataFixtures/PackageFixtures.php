<?php


namespace App\DataFixtures;

use App\Entity\Package;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class PackageFixtures extends Fixture {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        $this->_createPackage($orm, 'b2b_atarca', true);
        $this->_createPackage($orm, 'bulk_mailing', true);
        $this->_createPackage($orm, 'badges', true);
        $this->_createPackage($orm, 'reports', true);
        $this->_createPackage($orm, 'challenges', true);
        $this->_createPackage($orm, 'nft_wallet', true);
        $this->_createPackage($orm, 'qualifications', false);

    }

    private function _createPackage(ObjectManager $orm, $name, $purchased){
        $package = new Package();
        $package->setName($name);
        $package->setPurchased($purchased);

        $orm->persist($package);
        $orm->flush();
    }

}