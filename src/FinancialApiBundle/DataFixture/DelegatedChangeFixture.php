<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\DelegatedChange;
use App\FinancialApiBundle\Entity\DelegatedChangeData;
use App\FinancialApiBundle\Entity\Group;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class DelegatedChangeFixture extends Fixture implements DependentFixtureInterface {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        $dc = $this->createDelegatedChange($orm);

        $exchanger = $orm->getRepository(Group::class)->findOneBy(['type' => Group::ACCOUNT_TYPE_ORGANIZATION]);
        $private_accounts = $orm->getRepository(Group::class)->findBy(['type' => Group::ACCOUNT_TYPE_PRIVATE]);

        foreach($private_accounts as $account){
            $this->createDelegatedChangeData($orm, $dc, $exchanger, $account);
        }

    }

    /**
     * @param ObjectManager $orm
     */
    private function createDelegatedChange(ObjectManager $orm){
        $dc = new DelegatedChange();
        $dc->setScheduledAt(new \DateTime());
        $orm->persist($dc);
        $orm->flush();
        return $dc;
    }

    /**
     * @param ObjectManager $orm
     */
    private function createDelegatedChangeData(ObjectManager $orm, $dc, $exchanger, $account){
        $dcd = new DelegatedChangeData();
        $dcd->setDelegatedChange($dc);
        $dcd->setExchanger($exchanger);
        $dcd->setAccount($account);
        $dcd->setAmount(1000);
        $dcd->setStatus('new');
        $dcd->setPan('4111111111111111');
        $dcd->setExpiryDate('10/2024');
        $dcd->setCvv2('000');
        $dcd->setCreditcardId(1);
        $orm->persist($dcd);
        $orm->flush();
        return $dcd;
    }

    public function getDependencies(){
        return [
            AccountFixture::class,
        ];
    }
}