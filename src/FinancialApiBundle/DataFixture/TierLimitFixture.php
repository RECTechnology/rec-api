<?php

namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\StatusMethod;
use App\FinancialApiBundle\Entity\TierLimit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class TierLimitFixture
 * @package App\FinancialApiBundle\DataFixture
 */
class TierLimitFixture extends Fixture {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        $methods = ['rec' => 'REC', 'lemonway' => 'EUR'];
        $directions = ['in', 'out'];
        $tiers = range(0, 3);

        foreach ($methods as $method => $currency){
            foreach ($directions as $direction){
                foreach ($tiers as $tier){
                    $limit = $this->createUnlimitedTierLimit($tier, "$method-$direction", $currency);
                    $manager->persist($limit);
                }
            }
        }
        $manager->flush();
    }

    private function createUnlimitedTierLimit($tier, $method, $currency){
        $limit = new TierLimit();
        $limit->createDefault($tier, $method, $currency);
        $limit->setSingle(-1);
        $limit->setDay(-1);
        $limit->setWeek(-1);
        $limit->setMonth(-1);
        $limit->setYear(-1);
        $limit->setTotal(-1);
        return $limit;
    }
}