<?php


namespace App\DataFixtures;

use App\Entity\Campaign;
use App\Entity\CreditCard;
use App\Entity\DelegatedChange;
use App\Entity\DelegatedChangeData;
use App\Entity\Group;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class CreditCardFixtures extends Fixture implements DependentFixtureInterface {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {

        $company = $orm->getRepository(Group::class)->findOneBy(['type' => Group::ACCOUNT_TYPE_ORGANIZATION]);
        $user = $orm->getRepository(User::class)->findOneBy(['id' => 2]);

        $this->createCreditCard($orm, $company, $user);

        $thirdUser = $orm->getRepository(User::class)->findOneBy(['username' => '01234567C']);

        $this->createCreditCard($orm, $thirdUser->getActiveGroup(), $thirdUser);
    }

    /**
     * @param ObjectManager $orm
     */
    private function createCreditCard(ObjectManager $orm, $company, $user){
        $cc = new CreditCard();
        $cc->setUser($user);
        $cc->setCompany($company);
        $cc->setAlias('401288XXXXXX1881');
        $cc->setExternalId(304);
        $orm->persist($cc);
        $orm->flush();
        return $cc;
    }

    public function getDependencies(){
        return [
            AccountFixtures::class,
        ];
    }
}