<?php

namespace Test\FinancialApiBundle\Transactions;

use App\FinancialApiBundle\Controller\Google2FA;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Group;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class TransactionsTest
 * @package Test\FinancialApiBundle\Transactions
 * @group mongo
 */
class TransactionsTest extends BaseApiTest {

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
    }

    function testPay1RecToStoreWithPinTrueShouldFail(){
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $route = "/methods/v1/out/rec";
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

    function testWithdraw1RecFromCultureShopShouldReturn503(){
        //it return 503 because there is no mock for lemon withdraw, but if returns 503 means that the problem receiver
        // is not in campaign is fixed
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        /** @var Group $store */
        $store = $em->getRepository(Group::class)->findOneBy(array('name' => 'account_org_in_cult21'));
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $user = $this->getSignedInUser();
        $otp = Google2FA::oath_totp($user->two_factor_code);
        $route = "/admin/v3/accounts/{$store->getId()}/withdrawals";
        $resp = $this->rest(
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
    function testPay1RecWrongPinToStoreShouldReturn400(){
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $route = "/methods/v1/out/rec";
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
}
