<?php

namespace Test\FinancialApiBundle\Pos;

use App\FinancialApiBundle\Controller\Google2FA;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\DependencyInjection\App\Commons\Notifier;
use App\FinancialApiBundle\Entity\Notification;
use App\FinancialApiBundle\Entity\PaymentOrder;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\Utils\MongoDBTrait;

/**
 * Class AdminPaymentOrderRefundTest
 * @package Test\FinancialApiBundle\Pos
 */
class AdminPaymentOrderRefundTest extends BaseApiTest {

    use MongoDBTrait;

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

        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $paymentNotifications = $this->getPaymentNotifications($order);
        self::assertEquals(count($paymentNotifications), 1);
        $this->signOut();

        $this->setClientIp($this->faker->ipv4);

        $tx = $this->payOrder($order);
        self::assertEquals("success", $tx->status);
        $order = $this->readPaymentOrder($order);
        self::assertEquals(PaymentOrder::STATUS_DONE, $order->status);
        $order = $this->readPaymentOrderAdmin($order);
        self::assertObjectHasAttribute("payment_transaction", $order);
        self::assertNotEmpty($order->payment_transaction);

        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $paymentNotifications = $this->getPaymentNotifications($order);
        self::assertEquals(count($paymentNotifications), 2);
        $this->signOut();

        $order = $this->refundOrder($order);
        self::assertEquals(PaymentOrder::STATUS_REFUNDED, $order->status);
        self::assertObjectHasAttribute("refund_transaction", $order);
        self::assertNotEmpty($order->refund_transaction);

        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $paymentNotifications = $this->getPaymentNotifications($order);
        self::assertEquals(count($paymentNotifications), 3);

        $anotherAccount = $this->getAnotherAccount();
        $posWithoutUrlNotification = $this->createPosWithOutUrlNotification($anotherAccount);
        $this->activatePos($posWithoutUrlNotification);
        $orderWithOutNotification = $this->createPaymentOrder($pos, 1e8, $sample_url, $sample_url);
        $txWithOutNotification = $this->payOrder($orderWithOutNotification);
        self::assertEquals("success", $txWithOutNotification->status);
        $orderWithOutNotification = $this->readPaymentOrder($orderWithOutNotification);
        self::assertEquals(PaymentOrder::STATUS_DONE, $orderWithOutNotification->status);
        $orderWithOutNotification = $this->readPaymentOrderAdmin($orderWithOutNotification);
        self::assertObjectHasAttribute("payment_transaction", $orderWithOutNotification);
        self::assertNotEmpty($orderWithOutNotification->payment_transaction);

        $orderWithOutNotification = $this->refundOrder($orderWithOutNotification);
        self::assertEquals(PaymentOrder::STATUS_REFUNDED, $orderWithOutNotification->status);
        self::assertObjectHasAttribute("refund_transaction", $orderWithOutNotification);
        self::assertNotEmpty($orderWithOutNotification->refund_transaction);


    }

    private function getOneAccount()
    {
        $route = "/admin/v3/accounts";
        return $this->rest('GET', $route)[0];
    }

    private function getAnotherAccount()
    {
        $route = "/admin/v3/accounts";
        return $this->rest('GET', $route)[1];
    }

    private function getPaymentNotifications($order){
        $route = "/admin/v3/payment_order_notifications?payment_order_id='".$order->id."'";
        return $this->rest('GET', $route);
    }

    private function createPos($account)
    {
        $route = "/admin/v3/pos";
        return $this->rest('POST', $route, [
            'account_id' => $account->id,
            'notification_url' => "https://rec.barcelona"
        ]);
    }

    private function createPosWithOutUrlNotification($account)
    {
        $route = "/admin/v3/pos";
        return $this->rest('POST', $route, [
            'account_id' => $account->id
        ]);
    }

    private function createPaymentOrder($pos, int $amount, string $okUrl, string $koUrl)
    {
        $route = "/public/v3/payment_orders";
        $reference = "1234123412341234";
        $concept = "Mercat do castelo 1234123412341234";
        $nonce = round(microtime(true) * 1000, 0);
        $signatureParams = [
            'access_key' => $pos->access_key,
            'reference' => $reference,
            'ok_url' => $okUrl,
            'ko_url' => $koUrl,
            'signature_version' => 'hmac_sha256_v1',
            'amount' => $amount,
            'concept' => $concept,
            'payment_type' => 'desktop',
            'nonce' => $nonce
        ];
        ksort($signatureParams);
        $signatureData = json_encode($signatureParams, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $signatureData, base64_decode($pos->access_secret));
        $params = $signatureParams + ["signature" => $signature];
        return $this->rest('POST', $route, $params);
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
        self::assertCount(4, (array)$commerce);
        $route = "/methods/v3/out/rec";
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

    private function refundOrder($order)
    {
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $user = $this->getSignedInUser();
        $otp = Google2FA::oath_totp($user->two_factor_code);
        return $this->rest(
            'PUT',
            "/admin/v3/payment_orders/{$order->id}",
            [
                'status' => PaymentOrder::STATUS_REFUNDED,
                'otp' => $otp
            ]
        );
    }

}
