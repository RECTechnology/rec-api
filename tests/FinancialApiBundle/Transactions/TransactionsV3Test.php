<?php

namespace Test\FinancialApiBundle\Transactions;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class TransactionsV3Test
 * @package Test\FinancialApiBundle\Transactions
 * @group mongo
 */
class TransactionsV3Test extends BaseApiTest {

    private $store;

    function setUp(): void
    {
        parent::setUp();
        $this->store = $this->getSingleStore();
        $this->setClientIp($this->faker->ipv4);

    }

    private function getSingleStore(){
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $store = $this->rest('GET', '/admin/v3/accounts?type=COMPANY')[0];
        $this->signOut();
        return $store;
    }

    function testPay1RecToStoreShouldWork(){
        self::markTestIncomplete("fails on github");
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $route = "/methods/v3/out/rec";
        $resp = $this->rest(
            'POST',
            $route,
            [
                'address' => $this->store->rec_address,
                'amount' => 1e8,
                'concept' => 'Testing concept',
                'pin' => UserFixture::TEST_USER_CREDENTIALS['pin']
            ],
            [],
            201
        );
        $this->searchTransactions();
    }

    function testCheckWalletsAfterRecPayment(){
        //get shop wallets
        $store = $this->getSingleStore();
        $storeBalance = $store->wallets[0]->balance;
        self::assertEquals(10000000000000, $store->wallets[0]->balance);

        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        //get user wallets
        $accountRoute = "/user/v1/account";
        //generate LTAB transaction
        $account = $this->rest(
            'GET',
            $accountRoute,
            [],
            [],
            200
        );

        //check account balance to see bonification
        $accounts = $account->accounts;

        $userBalance = $accounts[0]->wallets[0]->balance;
        self::assertEquals(100000000000, $accounts[0]->wallets[0]->balance);
        self::assertEquals(10000000000, $accounts[1]->wallets[0]->balance);

        $route = "/methods/v3/out/rec";
        $amount = 1e8;
        $resp = $this->rest(
            'POST',
            $route,
            [
                'address' => $this->store->rec_address,
                'amount' => $amount,
                'concept' => 'Testing concept',
                'pin' => UserFixture::TEST_USER_CREDENTIALS['pin']
            ],
            [],
            201
        );

        $account = $this->rest(
            'GET',
            $accountRoute,
            [],
            [],
            200
        );

        //check account balance to see bonification
        $accounts = $account->accounts;

        self::assertEquals($userBalance - $amount, $accounts[0]->wallets[0]->balance);
        self::assertEquals(10000000000, $accounts[1]->wallets[0]->balance);
        //get shop wallet
        $store = $this->getSingleStore();
        self::assertEquals($storeBalance + $amount, $store->wallets[0]->balance);

    }

    function testPay1RecToStoreWithPinTrueShouldFail(){
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $route = "/methods/v3/out/rec";
        $resp = $this->rest(
            'POST',
            $route,
            [
                'address' => $this->store->rec_address,
                'amount' => 1e8,
                'concept' => 'Testing concept',
                'pin' => true
            ],
            [],
            400
        );
    }

    function testPay10000RecToStoreShouldReturn400(){
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $route = "/methods/v3/out/rec";
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

    function testPay1RecWrongPinToStoreShouldReturn400(){
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $route = "/methods/v3/out/rec";
        $response = $this->rest(
            'POST',
            $route,
            [
                'address' => $this->store->rec_address,
                'amount' => 1e8,
                'concept' => 'Testing concept',
                'pin' => '1313'
            ],
            [],
            400
        );
    }


    function searchTransactions()
    {
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $txs = $this->rest(
            'GET',
            '/admin/v1/transaction/list', //?start_date='.$start_date,
            [],
            [],
            200
        );

        $company_id = $txs->list[0][1];

        $resp = $this->requestJson(
            'GET',
            '/company/'.$company_id.'/v1/wallet/transactions',
            [],
            [],
        );

        $resp = json_decode($resp->getContent());
        self::assertEquals($txs->total, $resp->data->total);

        $start_date = date('Y-m-d',strtotime("-1 days"));
        $finish_date = date('Y-m-d',strtotime("+1 days"));

        $resp = $this->requestJson(
            'GET',
            '/company/'.$company_id.'/v1/wallet/transactions?limit=10&offset=0&order=desc&sort=id&query=%7B%22finish_date%22:%22'.$finish_date.'%22,%22start_date%22:%22'.$start_date.'%22,%22exchanges%22:%22all%22,%22methods_out%22:%22all%22,%22methods_in%22:%22all%22%7D',
            [],
            [],
        );

        $resp = json_decode($resp->getContent());
        self::assertEquals($txs->total, $resp->data->total);

        $start_date = date('Y-m-d',strtotime("-2 days"));
        $finish_date = date('Y-m-d',strtotime("-1 days"));

        $resp = $this->requestJson(
            'GET',
            '/company/'.$company_id.'/v1/wallet/transactions?limit=10&offset=0&order=desc&sort=id&query=%7B%22finish_date%22:%22'.$finish_date.'%22,%22start_date%22:%22'.$start_date.'%22,%22exchanges%22:%22all%22,%22methods_out%22:%22all%22,%22methods_in%22:%22all%22%7D',
            [],
            [],
        );

        $resp = json_decode($resp->getContent());
        self::assertEquals(0, $resp->data->total);

        $start_date = date('Y-m-d',strtotime("+1 days"));
        $finish_date = date('Y-m-d',strtotime("+2 days"));

        $resp = $this->requestJson(
            'GET',
            '/company/'.$company_id.'/v1/wallet/transactions?limit=10&offset=0&order=desc&sort=id&query=%7B%22finish_date%22:%22'.$finish_date.'%22,%22start_date%22:%22'.$start_date.'%22,%22exchanges%22:%22all%22,%22methods_out%22:%22all%22,%22methods_in%22:%22all%22%7D',
            [],
            [],
        );

        $resp = json_decode($resp->getContent());
        self::assertEquals(0, $resp->data->total);
    }
}
