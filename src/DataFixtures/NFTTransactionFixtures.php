<?php

namespace App\DataFixtures;

use App\Entity\Group;
use App\Entity\NFTTransaction;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class NFTTransactionFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        //Account creates topic 1, admin(id 7) mint token for this account
        $adminAccount = $orm->getRepository(Group::class)->find(7);
        $rezero_org_1 = $orm->getRepository(User::class)
            ->findOneBy(['username' => UserFixtures::TEST_REZERO_USER_1_CREDENTIALS['username']]);

        $this->createNftTransaction($orm, NFTTransaction::NFT_MINT, 1, $adminAccount, $rezero_org_1->getActiveGroup());

        //Other user likes topic
        $rezero_org_2 = $orm->getRepository(User::class)
            ->findOneBy(['username' => UserFixtures::TEST_REZERO_USER_2_CREDENTIALS['username']]);

        $this->createNftTransaction($orm, NFTTransaction::NFT_LIKE, 1, $rezero_org_2->getActiveGroup(), $rezero_org_2->getActiveGroup());

        //User share token with other colaborator
        $this->createNftTransaction($orm, NFTTransaction::NFT_SHARE, 1, $rezero_org_1->getActiveGroup(), $rezero_org_2->getActiveGroup());

    }

    private function createNftTransaction(ObjectManager $orm, $method, $topic_id, $from, $to){
        $tx = new NFTTransaction();
        $tx->setMethod($method);
        $tx->setStatus(NFTTransaction::STATUS_CREATED);
        $tx->setTopicId($topic_id);
        $tx->setFrom($from);
        $tx->setTo($to);

        $orm->persist($tx);
        $orm->flush();

    }

    public function getDependencies()
    {
        return [
            AccountFixtures::class
        ];
    }
}