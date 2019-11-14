<?php

namespace Test\FinancialApiBundle\Admin\LemonWay;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Financial\Driver\LemonWayDriver;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3WriteTestInterface;

/**
 * Class ApiConnectionTest
 * @package Test\FinancialApiBundle\Admin\LemonWay
 */
class DriverTest extends BaseApiTest {

    /** @var LemonWayDriver $lw */
    private $lw;

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $this->lw = $this->client->getContainer()->get('net.app.driver.lemonway.eur');
    }

    function testDriverIsLoaded() {
        $this->markTestIncomplete("parameters are not set in test env");
        $ip = $this->lw->getUserIP();
        self::assertRegExp("/[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+/", $ip);
    }

    function testLWConnection(){
        $this->markTestIncomplete("parameters are not set in test env");
        $walletName = "123456";
        $resp = $this->lw->callService(
            'GetWalletDetails',
            ["wallet" => $walletName]
        );
        self::assertNull($resp->E);
        $wallet = $resp->WALLET;
        self::assertEquals($walletName, $wallet->ID);
    }

}
