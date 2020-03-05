<?php


namespace Test\FinancialApiBundle\Database;


use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Document\Transaction;
use Doctrine\ODM\MongoDB\DocumentManager;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\Utils\MongoDBTrait;

class CheckODMConnectionTest extends BaseApiTest {

    use MongoDBTrait;

    public function testCheckConnectionIsOk(){
        /** @var DocumentManager $dm */
        $dm = self::createClient()->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $dm->getConnection()->connect();
        self::assertTrue($dm->getConnection()->isConnected());
    }

    function testPay1RecToStoreShouldWork(){
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $route = "/methods/v1/out/rec";
        $this->rest(
            'POST',
            $route,
            [
                'address' => $this->store->rec_address,
                'amount' => 1e8,
                'concept' => 'Testing concept',
                'pin' => UserFixture::TEST_USER_CREDENTIALS['pin']
            ]
        );
    }

    public function testListTransactions(){
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $resp = $this->requestJson('GET', '/user/v1/wallet/transactions');
        $this->assertTrue(true);
    }

    public function testCheckConnectionIsOk2(){
        /** @var DocumentManager $dm */
        $dm = self::createClient()->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $repo = $dm->getRepository(Transaction::class);
        $all = $repo->findAll();
        self::assertTrue($dm->getConnection()->isConnected());
    }
}