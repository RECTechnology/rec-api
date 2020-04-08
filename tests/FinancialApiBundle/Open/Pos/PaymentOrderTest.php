<?php

namespace Test\FinancialApiBundle\Open\Pos;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\Admin\AdminApiTest;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3WriteTestInterface;

/**
 * Class PaymentOrderTest
 * @package Test\FinancialApiBundle\Open\Pos
 */
class PaymentOrderTest extends BaseApiTest implements CrudV3WriteTestInterface {

    function testCreate()
    {
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $account = $this->getOneAccount();
        $pos = $this->createPos($account);

        $this->signOut();
        $sample_url = "https://rec.barcelona";
        $order = $this->createPaymentOrder($pos, 1, $sample_url, $sample_url);
        print($order);
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
        $route = "/public/v3/payment_orders";
        $reference = "1234";
        $concept = "1234";
        $signatureParams = [
            'access_key' => $pos->access_key,
            'reference' => $reference,
            'ok_url' => $okUrl,
            'ko_url' => $koUrl,
            'amount' => $amount,
            'concept' => $concept
        ];
        ksort($signatureParams);
        $signatureData = json_encode($signatureParams, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $signatureData, $pos->access_secret);
        return $this->rest('POST', $route, [
            'access_key' => $pos->access_key,
            'amount' => $amount,
            'ok_url' => $okUrl,
            'ko_url' => $koUrl,
            'concept' => $concept,
            'reference' => $reference,
            'signature_version' => 'hmac_sha256_v1',
            'signature' => $signature,
        ]);
    }

}
