<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\CreditCard;
use App\FinancialApiBundle\Entity\DelegatedChange;
use App\FinancialApiBundle\Entity\DelegatedChangeData;
use App\FinancialApiBundle\Entity\Group;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class CreditCardFixture extends Fixture implements DependentFixtureInterface {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {

        $exchanger = $orm->getRepository(Group::class)->findOneBy(['type' => Group::ACCOUNT_TYPE_ORGANIZATION]);
        $private_accounts = $orm->getRepository(Group::class)->findBy(['type' => Group::ACCOUNT_TYPE_PRIVATE]);

        $this->createCreditCard($orm, $exchanger, $private_accounts[0]);


    }

    /**
     * @param ObjectManager $orm
     */
    private function createCreditCard(ObjectManager $orm, $exchanger, $account){
        $cc = new CreditCard();
        $cc->setUser($account);
        $cc->setCompany($exchanger);
        $cc->setAlias('401288XXXXXX1881');
        $cc->setExternalId(1);
        $orm->persist($cc);
        $orm->flush();
        return $cc;
    }

    public function getDependencies(){
        return [
            UserFixture::class,
        ];
    }
}