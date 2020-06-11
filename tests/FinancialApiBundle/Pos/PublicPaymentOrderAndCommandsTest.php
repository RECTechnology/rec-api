<?php

namespace Test\FinancialApiBundle\Pos;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\DependencyInjection\App\Commons\Notifier;
use App\FinancialApiBundle\Entity\Notification;
use App\FinancialApiBundle\Entity\PaymentOrder;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\Utils\MongoDBTrait;

/**
 * Class PublicPaymentOrderAndCommandsTest
 * @package Test\FinancialApiBundle\Open\Pos
 */
class PublicPaymentOrderAndCommandsTest extends BaseApiTest {

    use MongoDBTrait;

    const SUCCESS_RESULT = true;
    const FAILURE_RESULT = false;

    function injectNotifier($result){
        $notifier = $this->createMock(Notifier::class);
        $notifier->method('send')
            ->will($this->returnCallback(
                function (Notification $ignored, $on_success, $on_failure, $on_finally) use ($result) {
                    if ($result) $on_success("success response");
                    else $on_failure("error response");
                    $on_finally();
                }
            ));
        $this->override(Notifier::class, $notifier);
    }

    function setUp(): void
    {
        parent::setUp();
        $this->injectNotifier(self::FAILURE_RESULT);
    }


    function testPayPollRefund()
    {
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $account = $this->getOneAccount();
        $pos = $this->createPos($account);
        $this->activatePos($pos);
        $this->listPosOrders($pos);

        $this->signOut();
        $sample_url = "https://rec.barcelona";
        $order = $this->createPaymentOrder($pos, 1e8, $sample_url, $sample_url);
        $this->paymentOrderHasRequiredData($order);
        $order = $this->readPaymentOrder($order);
        $this->paymentOrderHasRequiredData($order);
        $this->setClientIp($this->faker->ipv4);

        $tx = $this->payOrder($order);
        self::assertEquals("success", $tx->status);
        $order = $this->readPaymentOrder($order);
        self::assertEquals(PaymentOrder::STATUS_DONE, $order->status);
        $order = $this->readPaymentOrderAdmin($order);
        self::assertNotEmpty($order->payment_transaction);

        $order = $this->refundOrderPublic($order);
        self::assertEquals(PaymentOrder::STATUS_REFUNDED, $order->status);

        $this->runCommand('rec:pos:expire');

        $this->injectNotifier(self::SUCCESS_RESULT);
        $this->runCommand('rec:pos:notifications:retry');
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
            'notification_url' => "https://rec.barcelona"
        ]);
    }

    private function createPaymentOrder($pos, int $amount, string $okUrl, string $koUrl)
    {
        $route = "/public/v3/payment_orders";
        $reference = "1234123412341234";
        $concept = "Mercat do castelo 1234123412341234";
        $signatureParams = [
            'access_key' => $pos->access_key,
            'reference' => $reference,
            'ok_url' => $okUrl,
            'ko_url' => $koUrl,
            'signature_version' => 'hmac_sha256_v1',
            'amount' => $amount,
            'concept' => $concept
        ];
        ksort($signatureParams);
        $signatureData = json_encode($signatureParams, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $signatureData, base64_decode($pos->access_secret));
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

    private function readPaymentOrder($order)
    {
        return $this->rest('GET', "/public/v3/payment_orders/{$order->id}");
    }

    private function readPaymentOrderAdmin($order)
    {
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        return $this->rest('GET', "/admin/v3/payment_orders/{$order->id}");
    }

    private function activatePos($pos)
    {
        $route = "/admin/v3/pos/{$pos->id}";
        return $this->rest('PUT', $route, ['active' => true]);
    }

    private function paymentOrderHasRequiredData($order)
    {
        $requiredFields = ['created', 'updated', 'payment_address', 'payment_url', 'pos'];
        foreach ($requiredFields as $field){
            self::assertObjectHasAttribute($field, $order);
        }
    }

    private function payOrder($order)
    {
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $route = "/transaction/v1/vendor?address={$order->payment_address}";
        $commerce = $this->rest('GET', $route);
        self::assertCount(2, $commerce);
        $route = "/methods/v1/out/rec";
        $resp = $this->rest(
            'POST',
            $route,
            [
                'address' => $order->payment_address,
                'amount' => $order->amount,
                'concept' => 'Testing pay',
                'pin' => UserFixture::TEST_USER_CREDENTIALS['pin']
            ]
        );
        $this->signOut();
        return $resp;
    }

    private function listPosOrders($pos)
    {
        return $this->rest('GET', "/admin/v3/pos/{$pos->id}/payment_orders");
    }

    private function refundOrderPublic($order)
    {
        $signatureVersion = 'hmac_sha256_v1';
        $signatureParams = [
            'status' => PaymentOrder::STATUS_REFUNDED,
            'signature_version' => $signatureVersion,
        ];
        ksort($signatureParams);
        $signatureData = json_encode($signatureParams, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $signatureData, base64_decode($order->pos->access_secret));

        return $this->rest(
            'PUT',
            "/public/v3/payment_orders/{$order->id}",
            [
                'status' => PaymentOrder::STATUS_REFUNDED,
                'signature_version' => $signatureVersion,
                'signature' => $signature
            ]
        );
    }

}