<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\CreditCard;
use App\FinancialApiBundle\Entity\DelegatedChange;
use App\FinancialApiBundle\Entity\DelegatedChangeData;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\User;
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

        $company = $orm->getRepository(Group::class)->findOneBy(['type' => Group::ACCOUNT_TYPE_ORGANIZATION]);
        $user = $orm->getRepository(User::class)->findOneBy(['id' => 2]);


        $this->createCreditCard($orm, $company, $user);


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
            AccountFixture::class,
        ];
    }
}