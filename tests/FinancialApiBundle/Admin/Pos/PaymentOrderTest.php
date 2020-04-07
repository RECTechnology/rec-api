<?php

namespace Test\FinancialApiBundle\Admin\Pos;

use Test\FinancialApiBundle\Admin\AdminApiTest;
use Test\FinancialApiBundle\CrudV3WriteTestInterface;

/**
 * Class PaymentOrderTest
 * @package Test\FinancialApiBundle\Admin\Pos
 */
class PaymentOrderTest extends AdminApiTest implements CrudV3WriteTestInterface {

    const ROUTE = "/admin/v3/payment_order";

    function testCreate()
    {
        $account = $this->getOneAccount();
        $pos = $this->createPos($account);
        $sample_url = "https://rec.barcelona";
        $this->createPaymentOrder($pos, 1, $sample_url, $sample_url);
    }

    function testUpdate()
    {
    }

    function testDelete()
    {

    }

    private function getOneAccount()
    {
        $route = "/admin/v3/accounts";
        return $this->rest('GET', $route)[0];
    }

    private function createPos($account)
    {
        $route = "/admin/v3/pos";
        return $this->rest('POST', $route, [
            'account_id' => $account->id,
            'notification_url' => "https://rec.barcelona",
            'active' => true
        ]);
    }

    private function createPaymentOrder($pos, int $amount, string $okUrl, string $koUrl)
    {
        return $this->rest('POST', self::ROUTE, [
            'pos_id' => $pos->id,
            'amount' => $amount,
            'ok_url' => $okUrl,
            'ko_url' => $koUrl
        ]);
    }

}
