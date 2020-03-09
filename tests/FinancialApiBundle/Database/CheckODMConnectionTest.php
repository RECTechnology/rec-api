<?php


namespace Test\FinancialApiBundle\Database;


use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Document\Transaction;
use Doctrine\ODM\MongoDB\DocumentManager;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\Utils\MongoDBTrait;

class CheckODMConnectionTest extends BaseApiTest {

    use MongoDBTrait;

    protected function setUp(): void
    {
        parent::setUp();
        self::startMongo();
    }
    protected function tearDown(): void
    {
        self::stopMongo();
        parent::tearDown();
    }

    public function testCheckConnectionIsOk(){
        /** @var DocumentManager $dm */
        $dm = self::createClient()->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $dm->getConnection()->connect();
        self::assertTrue($dm->getConnection()->isConnected());
    }
}