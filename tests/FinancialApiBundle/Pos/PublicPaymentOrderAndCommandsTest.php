<?php

namespace Test\FinancialApiBundle\Pos;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\DependencyInjection\App\Commons\Notifier;
use App\FinancialApiBundle\Entity\Notification;
use App\FinancialApiBundle\Entity\PaymentOrder;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
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
        $pos = $this->preparePOS();
        $sample_url = "https://rec.barcelona";
        $resp = $this->createPaymentOrderWrongNonce($pos, 1e8, $sample_url, $sample_url);
        self::assertEquals(400, $resp->getStatusCode());
        $order = $this->createPaymentOrder($pos, 1e8, $sample_url, $sample_url);
        $this->paymentOrderHasRequiredData($order);
        $order = $this->readPaymentOrder($order);
        $this->paymentOrderHasRequiredData($order);
        $this->setClientIp($this->faker->ipv4);

        //try refundind tx in progress
        //necesitamos el order de admin porque necesitamos el access secret y en la publica no se devuelve
        $order = $this->readPaymentOrderAdmin($order);
        $resp = $this->refundOrderPublicInProgessShouldFail($order);
        self::assertEquals("error",$resp->status);
        self::assertEquals("Changing status is not allowed",$resp->message);
        $resp = $this->changeStatusFromInProgessToDoneShouldFail($order);

        $this->tryEditingOtherThanStatusShouldFail($order, $pos);

        $order = $this->readPaymentOrder($order);
        $tx = $this->payOrder($order);
        self::assertEquals("success", $tx->status);
        $order = $this->readPaymentOrder($order);
        self::assertEquals(PaymentOrder::STATUS_DONE, $order->status);
        $order = $this->readPaymentOrderAdmin($order);
        self::assertNotEmpty($order->payment_transaction);

        $this->refundOrderWithMoreAmountShouldFail($order);
        $order = $this->refundOrderPublic($order);
        self::assertEquals(PaymentOrder::STATUS_REFUNDED, $order->status);
        self::assertObjectHasAttribute('refunded_amount', $order);
        self::assertEquals($order->amount, $order->refunded_amount);

        $this->runCommand('rec:pos:expire');

        $this->injectNotifier(self::SUCCESS_RESULT);
        $this->runCommand('rec:pos:notifications:retry');

        //check if tx are done
        $order = $this->readPaymentOrderAdmin($order);
        self::assertEquals(PaymentOrder::STATUS_REFUNDED, $order->status);
        self::assertObjectHasAttribute('refund_transaction', $order);
        $refundTx = $order->refund_transaction;
        self::assertEquals(1e8, $refundTx->amount);

    }



    function testPayWrongPinRetry()
    {
        $pos = $this->preparePOS();
        $sample_url = "https://rec.barcelona";
        $order = $this->createPaymentOrder($pos, 1e8, $sample_url, $sample_url);
        $this->paymentOrderHasRequiredData($order);
        /** @var PaymentOrder $order */
        $order = $this->readPaymentOrder($order);
        $this->paymentOrderHasRequiredData($order);
        $this->setClientIp($this->faker->ipv4);

        $this->payOrderWrongPin($order);
        $this->payOrderWrongPin($order);
        $this->payOrderWrongPin($order);
        $tx = $this->payOrderWrongPin($order);
        self::assertEquals("Incorrect Pin", $tx->message);
    }

    function testPayWrongSignatureShouldFail(){

        $pos = $this->preparePOS();

        $route = "/public/v3/payment_orders";
        $reference = "1234123412341234";
        $concept = "Mercat do castelo 1234123412341234";
        $signature = 'badsignature_fsafl';
        $resp = $this->rest('POST', $route, [
            'access_key' => $pos->access_key,
            'amount' => 1e8,
            'ok_url' => "https://rec.barcelona",
            'ko_url' => "https://rec.barcelona",
            'concept' => $concept,
            'reference' => $reference,
            'signature_version' => 'hmac_sha256_v1',
            'signature' => $signature,
            'payment_type' => 'desktop',
            'nonce' => round(microtime(true) * 1000, 0)
        ], [], 400);

        self::assertEquals('Validation error', $resp->message);
        $errors = $resp->errors;
        self::assertEquals('signature is not valid', $errors[0]->message);

    }

    function testNotificationCommand()
    {

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('rec:pos:notifications:retry');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertEquals(0, $commandTester->getStatusCode());

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Listing order notifications', $output);

        //get all pos
        $respPos = $this->getAsAdmin('/admin/v3/pos');
        //We are generating 2 tx for tpv and one of each one has 2 notifications
        $this->assertStringContainsString((count($respPos) * 3) . ' notifications found', $output);
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
        //this one should work
        $resp1 = $this->rest('POST', $route, $params);
        //this one should fail because of same pos and nonce
        //Test replay
        $resp2 = $this->requestJson('POST', $route, $params);
        self::assertEquals(400, $resp2->getStatusCode());
        $content = json_decode($resp2->getContent(),true);
        $errors = $content['errors'];
        self::assertEquals("nonce not valid", $errors[0]['message']);
        return $resp1;
    }

    private function createPaymentOrderWrongNonce($pos, int $amount, string $okUrl, string $koUrl)
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
            'nonce' => $nonce -500000
        ];
        ksort($signatureParams);
        $signatureData = json_encode($signatureParams, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $signatureData, base64_decode($pos->access_secret));
        $params = $signatureParams + ["signature" => $signature];
        return $this->requestJson('POST', $route, $params);
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
        $requiredFields = ['id', 'amount', 'status', 'ko_url', 'ok_url', 'payment_type','created', 'updated', 'payment_address', 'payment_url', 'pos'];
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

    private function payOrderWrongPin($order)
    {
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $route = "/methods/v3/out/rec";
        $resp = $this->rest(
            'POST',
            $route,
            [
                'address' => $order->payment_address,
                'amount' => $order->amount,
                'concept' => 'Testing pay',
                'pin' => '1313'
            ],
            [],
            400
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
        $nonce = round(microtime(true) * 1000, 0);

        $signatureVersion = 'hmac_sha256_v1';
        $signatureParams = [
            'status' => PaymentOrder::STATUS_REFUNDED,
            'signature_version' => $signatureVersion,
            'nonce' => $nonce
        ];
        $signature = $this->calculateSignature($signatureParams, $order->pos);

        $params = $signatureParams + ["signature" => $signature];

        return $this->rest(
            'PUT',
            "/public/v3/payment_orders/{$order->id}",
            $params
        );
    }

    private function refundOrderPublicInProgessShouldFail($order)
    {
        $nonce = round(microtime(true) * 1000, 0);
        $signatureVersion = 'hmac_sha256_v1';
        $signatureParams = [
            'status' => PaymentOrder::STATUS_REFUNDED,
            'signature_version' => $signatureVersion,
            "nonce" => $nonce
        ];
        $signature = $this->calculateSignature($signatureParams, $order->pos);

        $params = $signatureParams + ["signature" => $signature];

        return $this->rest(
            'PUT',
            "/public/v3/payment_orders/{$order->id}",
            $params,
            [],
            400
        );
    }

    private function changeStatusFromInProgessToDoneShouldFail($order)
    {
        $nonce = round(microtime(true) * 1000, 0);
        $signatureVersion = 'hmac_sha256_v1';
        $signatureParams = [
            'status' => PaymentOrder::STATUS_DONE,
            'signature_version' => $signatureVersion,
            "nonce" => $nonce
        ];
        $signature = $this->calculateSignature($signatureParams, $order->pos);

        $params = $signatureParams + ["signature" => $signature];
        return $this->rest(
            'PUT',
            "/public/v3/payment_orders/{$order->id}",
            $params,
            [],
            403
        );
    }

    private function refundOrderWithMoreAmountShouldFail($order)
    {
        $nonce = round(microtime(true) * 1000, 0);
        $signatureVersion = 'hmac_sha256_v1';
        $signatureParams = [
            'status' => PaymentOrder::STATUS_REFUNDED,
            'signature_version' => $signatureVersion,
            'nonce' => $nonce,
            'refund_amount' => 2e8
        ];
        $signature = $this->calculateSignature($signatureParams, $order->pos);

        $params = $signatureParams + ["signature" => $signature];

        $this->rest(
            'PUT',
            "/public/v3/payment_orders/{$order->id}",
            $params,
            [],
            400
        );
    }

    private function preparePOS(){
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $account = $this->getOneAccount();
        $pos = $this->createPos($account);
        $this->activatePos($pos);
        $this->listPosOrders($pos);

        $this->signOut();

        return $pos;
    }

    function tryEditingOtherThanStatusShouldFail($order, $pos){
        $now = new \DateTime();
        $nonce = $now->getTimestamp();
        $signatureVersion = 'hmac_sha256_v1';
        $signatureParams = [
            'payment_address' => 'fake_address',
            'signature_version' => $signatureVersion,
            'nonce' => $nonce
        ];
        $signature = $this->calculateSignature($signatureParams, $order->pos);

        $params = $signatureParams + ["signature" => $signature];

        $resp = $this->requestJson(
            'PUT',
            "/public/v3/payment_orders/{$order->id}",
            $params
        );

        $updatedOrderResp = $this->requestJson('GET', "/public/v3/payment_orders/{$order->id}");
        $content = json_decode($updatedOrderResp->getContent(),true);
        self::assertNotEquals('fake_address', $content['data']['payment_address']);
    }

    /**
     * @param string $route
     */
    private function getAsAdmin(string $route)
    {
        $this->signOut();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $resp = $this->rest('GET', $route);
        $this->signOut();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        return $resp;

    }

    private function calculateSignature($signatureParams, $pos){
        ksort($signatureParams);
        $signatureData = json_encode($signatureParams, JSON_UNESCAPED_SLASHES);
        return hash_hmac('sha256', $signatureData, base64_decode($pos->access_secret));
    }

}
