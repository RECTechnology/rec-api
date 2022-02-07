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

class DelegatedChangeFixture extends Fixture implements DependentFixtureInterface {

    const AMOUNT  = 5500;

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        $dc = $this->createDelegatedChange($orm, 'DC1');

        $exchanger = $orm->getRepository(Group::class)->findOneBy(['id' => 6]);
        $sender = $orm->getRepository(Group::class)->findOneBy(['id' => 2]);
        $private_accounts = $orm->getRepository(Group::class)->findBy(['type' => Group::ACCOUNT_TYPE_PRIVATE]);
        $credit_card = $orm->getRepository(CreditCard::class)->findOneBy(['id' => 1]);

        foreach($private_accounts as $account){
            if(count($account->getCampaigns()) == 0){
                $this->createDelegatedChangeData($orm, $dc, $exchanger, $account, $credit_card, $sender);
            }
        }

        $dc2 = $this->createDelegatedChange($orm, 'DC2');


    }

    /**
     * @param ObjectManager $orm
     */
    private function createDelegatedChange(ObjectManager $orm, $name){
        $dc = new DelegatedChange();
        $dc->setName($name);
        $dc->setScheduledAt(new \DateTime());
        $dc->setStatus(DelegatedChange::STATUS_CREATED);
        $orm->persist($dc);
        $orm->flush();
        return $dc;
    }

    /**
     * @param ObjectManager $orm
     */
    private function createDelegatedChangeData(ObjectManager $orm, $dc, $exchanger, $account, $credit_card, $sender){
        $dcd = new DelegatedChangeData();
        $dcd->setDelegatedChange($dc);
        $dcd->setExchanger($exchanger);
        $dcd->setAccount($account);
        $dcd->setSender($sender);
        $dcd->setAmount(5500);
        $dcd->setStatus('new');
        #$dcd->setCreditcard($credit_card);
        $orm->persist($dcd);
        $orm->flush();
        return $dcd;
    }

    public function getDependencies(){
        return [
            CreditCardFixture::class,
        ];
    }
}