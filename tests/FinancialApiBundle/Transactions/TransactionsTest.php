<?php

namespace Test\FinancialApiBundle\Transactions;

use App\FinancialApiBundle\Controller\Google2FA;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\DependencyInjection\App\Commons\BalanceManipulator;
use App\FinancialApiBundle\DependencyInjection\App\Commons\LimitManipulator;
use App\FinancialApiBundle\Repository\TransactionRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Response;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\Utils\MongoDBTestInterface;
use Test\FinancialApiBundle\Utils\MongoDBTrait;

/**
 * Class TransactionsTest
 * @package Test\FinancialApiBundle\Transactions
 */
class TransactionsTest extends BaseApiTest implements MongoDBTestInterface {

    use MongoDBTrait;

    private $store;

    function setUp(): void
    {
        parent::setUp();
        $this->store = $this->getSingleStore();

        $repo = $this->createMock(TransactionRepository::class);
        $repo->method('sumLastDaysByMethod')->willReturn(0);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('persist');
        $dm->method('flush');
        $dm->method('getRepository')->willReturn($repo);
        //$this->override('doctrine.odm.mongodb.document_manager', $dm);

        $odm = $this->createMock(ManagerRegistry::class);
        $odm->method('getManager')->willReturn($dm);
        //$this->override('doctrine_mongodb', $odm);


        $lm = $this->createMock(LimitManipulator::class);
        $lm->method('checkLimits');
        $this->override('net.app.commons.limit_manipulator', $lm);

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


    function testPay10000RecToStoreShouldReturn400(){
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $route = "/methods/v1/out/rec";
        $this->rest(
            'POST',
            $route,
            [
                'address' => $this->store->rec_address,
                'amount' => 10000e8,
                'concept' => 'Testing concept',
                'pin' => UserFixture::TEST_USER_CREDENTIALS['pin']
            ],
            [],
            400
        );
    }

    function testWithdraw1RecShouldReturn503(){
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $user = $this->getSignedInUser();
        $otp = Google2FA::oath_totp($user->two_factor_code);
        $route = "/admin/v3/accounts/{$this->store->id}/withdrawals";
        $this->rest(
            'POST',
            $route,
            [
                'amount' => 100,
                'currency' => 'EUR',
                'concept' => 'Testing withdrawal',
                'otp' => $otp
            ],
            [],
            503
        );

    }

}
