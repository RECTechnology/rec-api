<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Controller\BaseApiV2Controller;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\KYC;
use App\FinancialApiBundle\Entity\Tier;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserGroup;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\UserWallet;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\FinancialApiBundle\Entity\Group as Account;
use Faker\Factory;
use Faker\Generator;
use phpDocumentor\Reflection\DocBlock\Description;

class TierFixture extends Fixture implements DependentFixtureInterface {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        $level1 = $this->createTier($orm, 'KYC0', 'First level', 0);
        $level2 = $this->createTier($orm, 'KYC1', 'Second level', 250, $level1);
        $this->createTier($orm, 'KYC2', 'Third level', null, $level2);
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