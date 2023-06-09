<?php

namespace App\Tests\User;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class UserWalletTest
 * @package App\Tests\User
 * @group mongo
 */
class UserWalletTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
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

        //no se como se pasa el filtro
        $user = $this->getSignedInUser();
        //creamos una in y una out para poder filtrar, se crea una en success y otra en created
        //TODO hay que hacer un ejemplo con filtros y revisar los que se necesitan
        $this->pay1RecToStore();
        $this->receive1Rec($user->group_data->rec_address);
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);

        $resp = $this->rest(
            'GET',
            '/user/v2/wallet/transactions',
            [],
            [],
            200
        );

    }

    private function pay1RecToStore(){
        $this->setClientIp($this->faker->ipv4);
        $store = $this->getSingleStore();
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
        $route = "/methods/v1/out/".$this->getCryptoMethod();
        $resp = $this->rest(
            'POST',
            $route,
            [
                'address' => $store->rec_address,
                'amount' => 1e8,
                'concept' => 'Testing concept',
                'pin' => UserFixtures::TEST_USER_CREDENTIALS['pin']
            ],
            [],
            201
        );
    }

    private function receive1Rec($address){
        $this->setClientIp($this->faker->ipv4);
        $this->signIn(UserFixtures::TEST_USER_LTAB_COMMERCE_CREDENTIALS);
        $route = "/methods/v1/out/".$this->getCryptoMethod();
        $resp = $this->rest(
            'POST',
            $route,
            [
                'address' => $address,
                'amount' => 1e8,
                'concept' => 'Testing concept',
                'pin' => UserFixtures::TEST_USER_LTAB_COMMERCE_CREDENTIALS['pin']
            ],
            [],
            201
        );
    }

    private function getSingleStore(){
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
        $store = $this->rest('GET', '/admin/v3/accounts?type=COMPANY')[0];
        $this->signOut();
        return $store;
    }

}
