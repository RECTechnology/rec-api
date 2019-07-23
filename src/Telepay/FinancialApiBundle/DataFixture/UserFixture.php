<?php


namespace Telepay\FinancialApiBundle\DataFixture;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Telepay\FinancialApiBundle\Entity\User;

class UserFixture extends Fixture {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user = new User();
        $manager->persist($user);
        $manager->flush();
    }
}