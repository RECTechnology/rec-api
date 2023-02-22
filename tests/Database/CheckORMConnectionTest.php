<?php


namespace App\Tests\Database;


use Doctrine\ORM\EntityManagerInterface;
use App\Tests\BaseApiTest;

class CheckORMConnectionTest extends BaseApiTest {

    public function testCheckConnectionIsOk(){
        /** @var EntityManagerInterface $em */
        $em = self::createClient()->getContainer()->get('doctrine.orm.entity_manager');
        $em->getConnection()->connect();
        self::assertTrue($em->getConnection()->isConnected());
    }
}