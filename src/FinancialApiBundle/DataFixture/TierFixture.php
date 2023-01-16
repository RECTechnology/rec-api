<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\Tier;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class TierFixture extends Fixture implements DependentFixtureInterface {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        $level1 = $this->createTier($orm, Tier::KYC_LEVELS[0], 'First level', 0);
        $level2 = $this->createTier($orm, Tier::KYC_LEVELS[1], 'Second level', 2500*1e8, $level1);
        $this->createTier($orm, Tier::KYC_LEVELS[2], 'Third level', null, $level2);
    }

    /**
     * @param ObjectManager $orm
     */
    private function createTier(ObjectManager $orm, $code, $description, $max_out, $parent_id=null){
        $tier = new Tier();
        $tier->setCode($code);
        $tier->setDescription($description);
        $tier->setParent($parent_id);
        $tier->setMaxOut($max_out);
        $orm->persist($tier);
        $orm->flush();
    }

    public function getDependencies(){
        return [
            UserFixture::class,
        ];
    }
}