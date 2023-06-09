<?php


namespace App\DataFixtures;

use App\Entity\Group;
use App\Entity\PaymentOrderUsedNonce;
use App\Entity\Pos;
use App\Entity\Tier;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class POSFixtures extends Fixture implements DependentFixtureInterface {

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

            $nonce = new PaymentOrderUsedNonce();
            $nonce->setPos($pos);
            $nonce->setNonce(123);

            $orm->persist($nonce);
            $orm->flush();
        }


    }

    public function getDependencies(){
        return [
            AccountFixtures::class,
        ];
    }
}