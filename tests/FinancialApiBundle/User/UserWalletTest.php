<?php

namespace Test\FinancialApiBundle\User;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Offer;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\Utils\MongoDBTrait;

/**
 * Class UserWalletTest
 * @package Test\FinancialApiBundle\User
 */
class UserWalletTest extends BaseApiTest
{
    use MongoDBTrait;

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
    }

    function testListCommerces()
    {
        $resp = $this->rest(
            'GET',
            '/user/v1/wallet/listCommerce',
            [],
            [],
            200
        );
    }

    function testGetLastTransactions()
    {
        $resp = $this->rest(
            'GET',
            '/user/v1/last',
            [],
            [],
            200
        );
    }

    function testGetWalletTransactionsV2()
    {
        $resp = $this->rest(
            'GET',
            '/user/v2/wallet/transactions',
            [],
            [],
            200
        );
    }

    function testGetWalletDayTransactions()
    {
        $resp = $this->rest(
            'GET',
            '/user/v1/wallet/day/transactions?day=2022-02-07',
            [],
            [],
            200
        );
    }

    function testGetWalletTransactionsV2WithFilters()
    {
        self::markTestIncomplete();
        //no se como se pasa el filtro
        $user = $this->getSignedInUser();
        //creamos una in y una out para poder filtrar, se crea una en success y otra en created
        $this->pay1RecToStore();
        $this->receive1Rec($user->group_data->rec_address);
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $resp = $this->rest(
            'GET',
            '/user/v2/wallet/transactions?query[status]',
            [],
            [],
            200
        );

    }

    private function pay1RecToStore(){
        $this->setClientIp($this->faker->ipv4);
        $store = $this->getSingleStore();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $route = "/methods/v1/out/rec";
        $resp = $this->rest(
            'POST',
            $route,
            [
                'address' => $store->rec_address,
                'amount' => 1e8,
                'concept' => 'Testing concept',
                'pin' => UserFixture::TEST_USER_CREDENTIALS['pin']
            ],
            [],
            201
        );
    }

    private function receive1Rec($address){
        $this->setClientIp($this->faker->ipv4);
        $this->signIn(UserFixture::TEST_USER_LTAB_COMMERCE_CREDENTIALS);
        $route = "/methods/v1/out/rec";
        $resp = $this->rest(
            'POST',
            $route,
            [
                'address' => $address,
                'amount' => 1e8,
                'concept' => 'Testing concept',
                'pin' => UserFixture::TEST_USER_LTAB_COMMERCE_CREDENTIALS['pin']
            ],
            [],
            201
        );
    }

    private function getSingleStore(){
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $store = $this->rest('GET', '/admin/v3/accounts?type=COMPANY')[0];
        $this->signOut();
        return $store;
    }

}
