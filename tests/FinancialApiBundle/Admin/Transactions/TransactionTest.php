<?php

namespace Test\FinancialApiBundle\Admin\Transactions;

use App\FinancialApiBundle\Controller\Google2FA;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Test\FinancialApiBundle\Admin\Base\AdminBaseCalls;

/**
 * Class TransactionTest
 * @package Test\FinancialApiBundle\Admin\Transactions
 * @group mongo
 */
class TransactionTest extends AdminBaseCalls {

    function setUp(): void
    {
        parent::setUp();
        $this->store = $this->getSingleStore();
        $this->setClientIp($this->faker->ipv4);

    }

    private function getSingleStore(){
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        //UserFixture::TEST_ADMIN_CREDENTIALS es el owner de esta tienda
        $store = $this->rest('GET', '/admin/v3/accounts?type=COMPANY')[0];
        return $store;
    }

    function testListTransactionsShouldWork(){

        $txs = $this->rest(
            'GET',
            '/admin/v1/transaction/list',
            [],
            [],
            200
        );

        self::assertObjectHasAttribute('total', $txs);
        self::assertObjectHasAttribute('limit', $txs);
        self::assertObjectHasAttribute('offset', $txs);
        self::assertObjectHasAttribute('list', $txs);
    }

    function testListTransactionsShuldReturnReceiverInPos(){

        //make a payment order
        $pos = $this->getOnePos();
        $sample_url = "https://rec.barcelona";
        $order = $this->createPaymentOrder($pos, 1e8, $sample_url, $sample_url);
        //we need to change ip to not enter in incoming ,if part, when ip = 127.0.0.1
        $this->setClientIp($this->faker->ipv4);
        $tx = $this->payOrder($order);

        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $txs = $this->requestJson('GET', '/admin/v1/transaction/list');

        $content = json_decode($txs->getContent(), true);

        $list = $content["data"]["list"];

        foreach ($list as $element){
            //4,5 and 6 are the receiver elements in the array
            self::assertNotEquals("-", $element[4]);
            self::assertNotEquals("-", $element[5]);
            self::assertNotEquals("-", $element[6]);
        }
    }

    private function getOnePos()
    {
        $route = "/admin/v3/pos";
        return $this->rest('GET', $route)[0];
    }

    function _testListTransactionsWithFilterShouldWork()
    {

        $txs = $this->rest(
            'GET',
            '/company/1/v1/wallet/transactions?limit=10&offset=0&order=desc&sort=id&query=%7B%22finish_date%22:%222022-01-20%22,%22start_date%22:%222020-01-01%22%7D',
            [],
            [],
            200
        );

        self::assertObjectHasAttribute('total', $txs);
        self::assertObjectHasAttribute('limit', $txs);
        self::assertObjectHasAttribute('offset', $txs);
        self::assertObjectHasAttribute('list', $txs);

    }

    function testListTransactionsByCompanyShouldWork(){

        $resp = $this->requestJson(
            'GET',
            '/company/1/v1/wallet/transactions',
            [],
            [],
        );

        $resp = json_decode($resp->getContent());

        self::assertObjectHasAttribute('data', $resp);
        $data = $resp->data;
        self::assertObjectHasAttribute('total', $data);
        self::assertObjectHasAttribute('daily', $data);
        self::assertObjectHasAttribute('daily_custom', $data);
        self::assertObjectHasAttribute('scales', $data);
        self::assertObjectHasAttribute('balance', $data);
        self::assertObjectHasAttribute('volume', $data);
        self::assertObjectHasAttribute('elements', $data);
    }

    function testPay1RecToStoreAndRefundShouldWork(){
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


        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $user = $this->getSignedInUser();
        $otp = Google2FA::oath_totp($user->two_factor_code);
        $route = "/admin/v3/transaction/refund";
        //El user no tiene la cuenta de la store activa, entonces debe petar

        $respRefund = $this->rest(
            'POST',
            $route,
            [
                'amount' => 1e8,
                'concept' => 'Refund Testing concept',
                'sec_code' => $otp,
                'txid' => $resp->pay_out_info->txid
            ],
            [],
            201
        );

        $content = $respRefund;

        $route = "/admin/v1/transaction/".$respRefund->id;

        $respInternal = $this->rest(
            'PUT',
            $route,
            [
                'internal' => 1,
            ],
            [],
            201
        );

        $content = $respInternal;
    }
}
