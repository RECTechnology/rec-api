<?php

namespace Test\FinancialApiBundle\Transactions;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\DependencyInjection\App\Commons\BalanceManipulator;
use App\FinancialApiBundle\Repository\TransactionRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Response;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class StorePaymentTransactionTest
 * @package Test\FinancialApiBundle\Transactions
 */
class StorePaymentTransactionTest extends BaseApiTest {

    private $store;

    function setUp(): void
    {
        parent::setUp();
        $this->store = $this->getSingleStore();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);

        $repo = $this->createMock(TransactionRepository::class);
        $repo->method('sumLastDaysByMethod')->willReturn(0);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('persist');
        $dm->method('flush');
        $dm->method('getRepository')->willReturn($repo);
        $this->override('doctrine.odm.mongodb.document_manager', $dm);

        $odm = $this->createMock(ManagerRegistry::class);
        $odm->method('getManager')->willReturn($dm);
        $this->override('doctrine_mongodb', $odm);

        $bm = $this->createMock(BalanceManipulator::class);
        $bm->method('addBalance');
        $this->override('net.app.commons.balance_manipulator', $bm);

        $this->setClientIp($this->faker->ipv4);
    }

    private function getSingleStore(){
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $resp = $this->requestJson('GET', '/admin/v3/accounts?type=COMPANY');
        $store = json_decode($resp->getContent())->data->elements[0];
        $this->signOut();
        return $store;
    }

    function testPay1RecToStoreShouldWork(){
        $route = "/methods/v1/out/rec";
        $resp = $this->requestJson(
            'POST',
            $route,
            [
                'address' => $this->store->rec_address,
                'amount' => 1e8,
                'concept' => 'Testing concept',
                'pin' => UserFixture::TEST_USER_CREDENTIALS['pin']
            ]
        );

        self::assertEquals(
            Response::HTTP_CREATED,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

}
