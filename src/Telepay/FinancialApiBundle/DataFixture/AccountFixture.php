<?php


namespace Telepay\FinancialApiBundle\DataFixture;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Telepay\FinancialApiBundle\Entity\Group as Account;

class AccountFixture extends Fixture {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $account = new Account();
        $manager->persist($account);
        $manager->flush();
    }

    public function getDependencies(){
        return [
            UserFixture::class,
        ];
    }
}