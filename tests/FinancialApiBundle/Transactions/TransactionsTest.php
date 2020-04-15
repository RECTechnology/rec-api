<?php

namespace Test\FinancialApiBundle\Transactions;

use App\FinancialApiBundle\Controller\Google2FA;
use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\Utils\MongoDBTrait;

/**
 * Class TransactionsTest
 * @package Test\FinancialApiBundle\Transactions
 */
class TransactionsTest extends BaseApiTest {

    use MongoDBTrait;

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
