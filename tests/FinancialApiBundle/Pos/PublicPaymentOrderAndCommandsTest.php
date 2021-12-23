<?php

namespace Test\FinancialApiBundle\Pos;

use App\FinancialApiBundle\DataFixture\AccountFixture;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\DependencyInjection\App\Commons\Notifier;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\Mailing;
use App\FinancialApiBundle\Entity\MailingDelivery;
use App\FinancialApiBundle\Entity\Notification;
use App\FinancialApiBundle\Entity\PaymentOrder;
use App\FinancialApiBundle\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
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

        $all = $this->rest('GET', "/admin/v3/accounts");

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
            'concept' => $concept,
            'payment_type' => 'desktop',
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
            'payment_type' => 'desktop',
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
        self::assertCount(4, (array)$commerce);
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
    private function payOrderWrongPin($order)
    {
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $route = "/methods/v1/out/rec";
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
    function testPayWrongPinRetry()
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
        /** @var PaymentOrder $order */
        $order = $this->readPaymentOrder($order);
        $this->paymentOrderHasRequiredData($order);
        $this->setClientIp($this->faker->ipv4);

        $this->payOrderWrongPin($order);
        $this->payOrderWrongPin($order);
        $this->payOrderWrongPin($order);
        $tx = $this->payOrderWrongPin($order);
        self::assertEquals("Failed payment transaction", $tx->message);
    }

    function testBonissimAccountPaysToBonissimCommerceShouldSuccess(){
        $this->setClientIp($this->faker->ipv4);

        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);

        // getting commerce list
        $commerces =  $this->rest('GET', "/user/v3/accounts?campaigns=1&type=COMPANY");
        self::assertGreaterThanOrEqual(1, count($commerces));
        $commerce = $commerces[0];

        // getting user's owned accounts
        $myAccounts = $this->rest('GET', '/user/v1/companies');
        $foundBonissimAccount = false;
        foreach($myAccounts as $account) {
            // checking if the account has campaings
            if (!$foundBonissimAccount && count($account->company->campaigns) > 0) {
                self::assertEquals(Campaign::BONISSIM_CAMPAIGN_NAME, $account->company->campaigns[0]->name);
                $foundBonissimAccount = true;
                $user_id = $account->company->kyc_manager->id;
                $rewarded_amount = $account->company->rewarded_amount;
                // changing the active account for the current user
                $this->rest('PUT', '/user/v1/activegroup', ['group_id' => $account->company->id]);

                //pay to commerce
                $this->rest(
                    'POST',
                    '/methods/v1/out/rec',
                    [
                        'address' => $commerce->rec_address,
                        'amount' => 10e8,
                        'concept' => 'Testing concept',
                        'pin' => UserFixture::TEST_USER_CREDENTIALS['pin']
                    ]
                );
            }
        }
        self::assertTrue($foundBonissimAccount);
        $private_bonissim_account = $this->rest('GET', "/user/v3/accounts?campaigns=1&type=PRIVATE&kyc_manager=".$user_id);

        $this->reportLTAB();
    }

    function testBonissimAccountPaysToNoBonissimCommerceShouldFail(){
        $this->setClientIp($this->faker->ipv4);

        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);

        // getting commerce list
        $commerces =  $this->rest('GET', "/user/v3/accounts?type=COMPANY");
        self::assertGreaterThanOrEqual(1, count($commerces));

        foreach($commerces as $commerce) {
            if($commerce->name != Campaign::BONISSIM_CAMPAIGN_NAME){
                $not_bonissim_account = $commerce;
            }
        }
        self::assertTrue(isset($not_bonissim_account));

        // getting user's owned accounts
        $myAccounts = $this->rest('GET', '/user/v1/companies');
        $foundBonissimAccount = false;
        foreach($myAccounts as $account) {
            // checking if the account has campaings
            if (!$foundBonissimAccount && count($account->company->campaigns) > 0) {
                self::assertEquals(Campaign::BONISSIM_CAMPAIGN_NAME, $account->company->campaigns[0]->name);
                $foundBonissimAccount = true;
                // changing the active account for the current user
                $this->rest('PUT', '/user/v1/activegroup', ['group_id' => $account->company->id]);

                //pay to commerce
                $this->rest(
                    'POST',
                    '/methods/v1/out/rec',
                    [
                        'address' => $not_bonissim_account->rec_address,
                        'amount' => 10e8,
                        'concept' => 'Testing concept',
                        'pin' => UserFixture::TEST_USER_CREDENTIALS['pin']
                    ],
                    [],
                    400
                );
            }
        }
        self::assertTrue($foundBonissimAccount);
    }

    function testAccountPaysToBonissimCommerceShouldSend15P(){
        $this->setClientIp($this->faker->ipv4);

        $campaign = $this->getAsAdmin("/admin/v3/campaign/1");
        $campaign_account = $this->getAsAdmin("/admin/v3/group/" . $campaign->campaign_account);

        $private_account = $this->getAsAdmin('/admin/v3/accounts?name='.AccountFixture::TEST_ACCOUNT_LTAB_PRIVATE['name'].'_private')[0];
        $bonissim_private_accounts = $this->getAsAdmin('/admin/v3/accounts?name='.Campaign::BONISSIM_CAMPAIGN_NAME);

        $bonissim_private_account = null;
        foreach ($bonissim_private_accounts as $account){
            if($account->kyc_manager->username == $private_account->kyc_manager->username){
                $bonissim_private_account = $account;
            }
        }

        $bonissim_company_account =  $this->getAsAdmin('/admin/v3/accounts?name='.AccountFixture::TEST_ACCOUNT_LTAB_COMMERCE['name'])[0];

        self::assertTrue(isset($private_account));
        self::assertTrue(isset($bonissim_private_account));
        self::assertTrue(isset($bonissim_company_account));

        $redeemable = 50;
        $tx_amount = 2.5;

        $bonissim_private_account = $this->setRedeemable($bonissim_private_account, $redeemable);

        $this->signIn(UserFixture::TEST_USER_LTAB_CREDENTIALS);

        // changing the active account for the current user
        $this->rest('PUT', '/user/v1/activegroup', ['group_id' => $private_account->id]);

        //pay to commerce
        $this->rest(
            'POST',
            '/methods/v1/out/rec',
            [
                'address' => $bonissim_company_account->rec_address,
                'amount' => $tx_amount * 1e8,
                'concept' => 'Testing concept',
                'pin' => UserFixture::TEST_USER_LTAB_CREDENTIALS['pin']
            ]
        );

        $this->runCommand('rec:crypto:check');
        $this->runCommand('rec:crypto:check');

        $_campaign_account = $this->getAsAdmin("/admin/v3/group/" . $campaign->campaign_account);
        $_bonissim_private_account = $this->getAsAdmin("/admin/v3/group/" . $bonissim_private_account->id);

        self::assertEquals($redeemable - $tx_amount, $_bonissim_private_account->redeemable_amount);
        self::assertEquals($tx_amount, $_bonissim_private_account->rewarded_amount);
        $expected_amount = round($tx_amount / 100 * 15, 2) * 1e8;
        $payed_to_comerce = ($campaign_account->wallets[0]->balance - $_campaign_account->wallets[0]->balance);
        self::assertEquals($expected_amount, $payed_to_comerce);
        $payed_to_user = ($_bonissim_private_account->wallets[0]->balance - $bonissim_private_account->wallets[0]->balance);
        self::assertEquals($expected_amount, $payed_to_user);

        $this->reportLTAB();
    }

    function reportLTAB()
    {
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $route = "/admin/v3/accounts/reports/ltab_general";
        $resp = $this->request('POST', $route, null, [],
            ['since' => '2020-01-10', 'to' => '2021-10-01']);
        $output = $this->runCommand('rec:mailing:send');
        self::assertRegExp("/Processing/", $output);
    }

    /**
     * @param $bonissim_private_account
     * @param float $redeemable
     */
    private function setRedeemable($bonissim_private_account, float $redeemable)
    {
        $this->signOut();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $route = "/admin/v3/group/" . $bonissim_private_account->id;
        $resp = $this->rest('PUT', $route, ["redeemable_amount" => $redeemable]);
        $bonissim_private_account = $this->rest('GET', "/admin/v3/group/" . $bonissim_private_account->id);
        self::assertEquals($redeemable, $bonissim_private_account->redeemable_amount);
        $this->signOut();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        return $bonissim_private_account;
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

    function testAccountPaysToNoBonissimCommerceShouldRecuceRedeemable()
    {
        $this->setClientIp($this->faker->ipv4);


        $accounts = $this->getAsAdmin("/admin/v3/accounts");
        self::assertGreaterThanOrEqual(1, count($accounts));

        $private_account = $this->getAsAdmin('/admin/v3/accounts?name='.AccountFixture::TEST_ACCOUNT_LTAB_PRIVATE['name'].'_private')[0];
        $bonissim_private_accounts = $this->getAsAdmin('/admin/v3/accounts?name='.Campaign::BONISSIM_CAMPAIGN_NAME);
        $company_account = $this->getAsAdmin('/admin/v3/accounts?name=COMMERCEACCOUNT')[0];

        $bonissim_private_account = null;
        foreach ($bonissim_private_accounts as $account){
            if($account->kyc_manager->username == $private_account->kyc_manager->username){
                $bonissim_private_account = $account;
            }
        }

        self::assertTrue(isset($private_account));
        self::assertTrue(isset($bonissim_private_account));
        self::assertTrue(isset($company_account));

        $redeemable = 50000000;
        $tx_amount = 100;
        $bonissim_private_account = $this->setRedeemable($bonissim_private_account, $redeemable);

        $this->signIn(UserFixture::TEST_USER_LTAB_CREDENTIALS);
        // changing the active account for the current user
        $this->rest('PUT', '/user/v1/activegroup', ['group_id' => $private_account->id]);

        //pay to commerce
        $this->rest(
            'POST',
            '/methods/v1/out/rec',
            [
                'address' => $company_account->rec_address,
                'amount' => $tx_amount * 1e8,
                'concept' => 'Testing concept',
                'pin' => UserFixture::TEST_USER_LTAB_CREDENTIALS['pin']
            ]
        );

        $_bonissim_private_account = $this->getAsAdmin("/admin/v3/group/" . $bonissim_private_account->id);
        $usar_total_balance = 0;
        foreach ($accounts as $account){
            if($account->kyc_manager->username == $private_account->kyc_manager->username and
                $account->type == 'PRIVATE' and
                $account->name != Campaign::BONISSIM_CAMPAIGN_NAME){
                $usar_total_balance += $account->wallets[0]->available;
            }
        }
        $max_redeemable = max($usar_total_balance  / 1e8 - $tx_amount, 0);
        self::assertEquals($max_redeemable, $_bonissim_private_account->redeemable_amount);
    }


    function testPayKycCheck(): void
    {
        $this->setClientIp($this->faker->ipv4);
        $reciver = $this->getAsAdmin('/admin/v3/accounts?level=2')[1];

        $resp = $this->rest(
            'POST',
            '/methods/v1/out/rec',
            [
                'address' => $reciver->rec_address,
                'amount' => 300e8,
                'concept' => 'Testing concept',
                'pin' => UserFixture::TEST_USER_CREDENTIALS['pin']
            ],
            [],
            400
        );

    }

    function testPrivateNoCultureAccountPayToCultureCommerceShouldFail(): void
    {
        $this->setClientIp($this->faker->ipv4);
        $reciver = $this->getAsAdmin('/admin/v3/accounts?name='.AccountFixture::TEST_ACCOUNT_CULT21_COMMERCE['name'])[0];
        $sender = $this->getAsAdmin('/admin/v3/accounts?name='.UserFixture::TEST_THIRD_USER_CREDENTIALS['name'])[0];
        $start_balance = $sender->wallets[0]->balance;
        $this->signIn(UserFixture::TEST_THIRD_USER_CREDENTIALS);
        echo'';
        $resp = $this->rest(
            'POST',
            '/methods/v1/out/rec',
            [
                'address' => $reciver->rec_address,
                'amount' => 10e8,
                'concept' => 'Testing concept',
                'pin' => UserFixture::TEST_THIRD_USER_CREDENTIALS['pin']
            ],
            [],
            400
        );

        $this->runCommand('rec:crypto:check');
        $this->runCommand('rec:crypto:check');

        $_sender = $this->getAsAdmin('/admin/v3/accounts?name='.UserFixture::TEST_THIRD_USER_CREDENTIALS['name'])[0];
        $end_balance = $_sender->wallets[0]->balance;
        self::assertEquals($start_balance, $end_balance);

    }


}
