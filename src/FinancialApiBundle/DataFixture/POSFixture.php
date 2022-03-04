<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\Pos;
use App\FinancialApiBundle\Entity\Tier;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class POSFixture extends Fixture implements DependentFixtureInterface {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        $this->createPOS($orm);
    }


    /**
     * @param ObjectManager $orm
     */
    private function createPOS(ObjectManager $orm){
        $accounts = $orm->getRepository(Group::class)->findBy(array(
            'type' => 'COMPANY'
        ));
        $totalAccounts = count($accounts) -2;
        //generemos tpv para todos los comercios menos los dos ultimos para poder generarlo despues desde los tests y que no falle
        //con dupolicated resource ya que la relacion es OneToOne y no puede tener dos tpvs
        for ($i =0; $i< $totalAccounts; $i++){
            $pos = new Pos();
            $pos->setActive(true);
            $pos->setAccount($accounts[$i]);
            $pos->setNotificationUrl('https://webhook.site/ba1ec65d-affe-41f4-b489-ddc');
            $orm->persist($pos);
            $orm->flush();
        }


    }

    public function getDependencies(){
        return [
            AccountFixture::class,
        ];
    }
}