<?php

namespace Test\FinancialApiBundle\Database;


use Doctrine\ODM\MongoDB\DocumentManager;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * @group mongo
 */
class CheckODMConnectionTest extends BaseApiTest {

    public function testCheckConnectionIsOk(){
        /** @var DocumentManager $dm */
        $dm = self::createClient()->getContainer()->get('doctrine_mongodb.odm.document_manager');
        $dm->getConnection()->connect();
        self::assertTrue($dm->getConnection()->isConnected());
    }
}