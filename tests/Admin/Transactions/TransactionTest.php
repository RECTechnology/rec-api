<?php

namespace App\Tests\Admin\Transactions;

use App\Controller\Google2FA;
use App\DataFixtures\UserFixtures;
use App\Entity\Group;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Tests\Admin\Base\AdminBaseCalls;

/**
 * Class TransactionTest
 * @package App\Tests\Admin\Transactions
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
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
        //UserFixtures::TEST_ADMIN_CREDENTIALS es el owner de esta tienda
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

        self::assertIsObject($txs);
        self::assertTrue(property_exists($txs, 'total'));
        self::assertTrue(property_exists($txs, 'limit'));
        self::assertTrue(property_exists($txs, 'offset'));
        self::assertTrue(property_exists($txs, 'list'));
    }

    function testListTransactionsShuldReturnReceiverInPos(){

        //make a payment order
        $pos = $this->getOnePos();
        $sample_url = "https://rec.barcelona";
        $order = $this->createPaymentOrder($pos, 1e8, $sample_url, $sample_url);
        //we need to change ip to not enter in incoming ,if part, when ip = 127.0.0.1
        $this->setClientIp($this->faker->ipv4);
        $tx = $this->payOrder($order);

        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
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

        self::assertIsObject($txs);
        self::assertTrue(property_exists($txs, 'total'));
        self::assertTrue(property_exists($txs, 'limit'));
        self::assertTrue(property_exists($txs, 'offset'));
        self::assertTrue(property_exists($txs, 'list'));

    }

    function testListTransactionsByCompanyShouldWork(){

        $resp = $this->requestJson(
            'GET',
            '/company/1/v1/wallet/transactions',
            [],
            [],
        );

        $resp = json_decode($resp->getContent());

        self::assertIsObject($resp);
        self::assertTrue(property_exists($resp, 'data'));
        $data = $resp->data;

        self::assertIsObject($data);
        self::assertTrue(property_exists($data, 'total'));
        self::assertTrue(property_exists($data, 'daily'));
        self::assertTrue(property_exists($data, 'daily_custom'));
        self::assertTrue(property_exists($data, 'scales'));
        self::assertTrue(property_exists($data, 'balance'));
        self::assertTrue(property_exists($data, 'volume'));
        self::assertTrue(property_exists($data, 'elements'));
    }

    function testPay1RecToStoreAndRefundShouldWork(){
        self::markTestIncomplete("fails on github");
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
        $route = "/methods/v3/out/".$this->getCryptoMethod();
        $resp = $this->rest(
            'POST',
            $route,
            [
                'address' => $this->store->rec_address,
                'amount' => 1e8,
                'concept' => 'Testing concept',
                'pin' => UserFixtures::TEST_USER_CREDENTIALS['pin']
            ],
            [],
            201
        );

        $route = "/methods/v3/out/".$this->getCryptoMethod()."/".$resp->id;
        $resp = $this->rest(
            'GET',
            $route,
            [],
            [],
            200
        );


        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
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
                'txid' => $resp->pay_out_info->txid,
                'internal_in' => 1,
                'internal_out' => 1
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

    function testAdminThirdTransaction(){
        $route = '/admin/v3/third/'.$this->getCryptoMethod();
        $data = [
            'amount' => 1e8
        ];
        $resp = $this->requestJson('POST', $route, $data);

        $content = json_decode($resp->getContent(), true);

        self::assertEquals("Missing parameter sender", $content['message']);

        $data = [
            'amount' => 1e8,
            'sender' => 5
        ];
        $resp = $this->requestJson('POST', $route, $data);

        $content = json_decode($resp->getContent(), true);

        self::assertEquals("Missing parameter receiver", $content['message']);

        $data = [
            'amount' => 1e8,
            'sender' => 5,
            'receiver' => 9
        ];
        $resp = $this->requestJson('POST', $route, $data);

        $content = json_decode($resp->getContent(), true);

        self::assertEquals("Missing parameter sec_code", $content['message']);

        $user = $this->getSignedInUser();
        $otp = Google2FA::oath_totp($user->two_factor_code);
        $data = [
            'amount' => 1e8,
            'sender' => 5,
            'receiver' => 9,
            'sec_code' => $otp
        ];
        $resp = $this->requestJson('POST', $route, $data);

        $content = json_decode($resp->getContent(), true);

        self::assertEquals("Missing parameter concept", $content['message']);

        $data = [
            'amount' => 1e8,
            'sender' => 5,
            'receiver' => 9,
            'sec_code' => $otp,
            'concept' => "Random concept"
        ];
        $resp = $this->requestJson('POST', $route, $data);

        $content = json_decode($resp->getContent(), true);

        self::assertEquals("success", $content['status']);

        $data = [
            'amount' => 1e8,
            'sender' => 5,
            'receiver' => 9,
            'sec_code' => $otp,
            'concept' => "Random concept",
            'internal_out' => 1,
            'internal_in' => 1
        ];
        $resp = $this->requestJson('POST', $route, $data);

        $content = json_decode($resp->getContent(), true);

        self::assertEquals("success", $content['status']);

    }
}
