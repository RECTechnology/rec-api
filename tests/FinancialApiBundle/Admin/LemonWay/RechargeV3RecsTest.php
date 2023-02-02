<?php

namespace Test\FinancialApiBundle\Admin\LemonWay;

use App\FinancialApiBundle\DataFixture\AccountFixture;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\AccountCampaign;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Financial\Methods\LemonWayMethod;
use Test\FinancialApiBundle\Admin\AdminApiTest;

/**
 * Class RechargeV3RecsTest
 * @package Test\FinancialApiBundle\Admin\LemonWay
 * @group mongo
 */
class RechargeV3RecsTest extends AdminApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->setClientIp($this->faker->ipv4);

    }

    function testLemonTransfer(){
        //this test only pass if parameter group_root_id = 1
        $user_id = 1;
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        $user_pin = $em->getRepository(User::class)->findOneBy(['id' => $user_id])->getPin();

        $route = "/admin/v3/user/{$user_id}";
        $resp = $this->rest('PUT', $route, ['private_tos_campaign' => true]);
        self::assertTrue($resp->private_tos_campaign);

        $private_bonissim_account = $this->rest('GET', "/user/v3/accounts?campaigns=1&type=PRIVATE&kyc_manager=".$user_id);
        self::assertCount(0, $private_bonissim_account);

        $private_accounts = $this->rest('GET', "/user/v3/accounts?type=PRIVATE&kyc_manager=".$user_id);
        self::assertGreaterThanOrEqual(1, count($private_accounts));
        $private_account_id = $private_accounts[0]->id;

        $acc = $em->getRepository(Group::class)->find($private_account_id);
        $acc->setCompanyImage('');
        $em->persist($acc);
        $em->flush();

        //remove this account from roses campaign
        $account_campaigns = $em->getRepository(AccountCampaign::class)->findBy(array('account' => $acc));
        foreach ($account_campaigns as $accountCampaign){
            $em->remove($accountCampaign);
            $em->flush();
        }

        $company_accounts = $this->rest('GET', "/user/v3/accounts?type=COMPANY&kyc_manager=".$user_id);
        self::assertGreaterThanOrEqual(1, count($company_accounts));
        $company_account_id = $company_accounts[0]->id;

        $data = ['status' => Transaction::$STATUS_RECEIVED,
            'company_id' => $private_account_id,
            'amount' => 6000,
            'commerce_id' => $company_account_id,
            'concept' => 'test recharge',
            'pin' => $user_pin,
            'save_card' => 0];

        $this->useLemonWayMock($data);

        $rest_group1 = $this->requestJson('GET', '/admin/v3/group/'.$private_account_id);
        $before = json_decode($rest_group1->getContent(), true);

        //make first recharge
        $response = $this->executeRecharge($data);

        //check recharge
        $rest_group2 = $this->requestJson('GET', '/admin/v3/group/'.$private_account_id);
        $after = json_decode($rest_group2->getContent(), true);
        self::assertEquals($before["data"]["wallets"][0]["balance"] + $data['amount'] * 1e6,
            $after["data"]["wallets"][0]["balance"]);

        //check create bonissim account
        $private_bonissim_account = $this->rest('GET', "/user/v3/accounts?campaigns=1&type=PRIVATE&kyc_manager=".$user_id);
        self::assertCount(1, $private_bonissim_account);
        self::assertEquals(60, $private_bonissim_account[0]->redeemable_amount);

        $data = ['status' => Transaction::$STATUS_RECEIVED,
            'company_id' => $private_account_id,
            'amount' => 2000,
            'commerce_id' => $company_account_id,
            'concept' => 'test recharge',
            'pin' => $user_pin,
            'save_card' => 0];

        //update mock
        $this->useLemonWayMock($data);
        //make first recharge
        $this->executeRecharge($data);

        //check no new bonissim account is created
        $private_bonissim_account = $this->rest('GET', "/user/v3/accounts?campaigns=1&type=PRIVATE&kyc_manager=".$user_id);
        self::assertCount(1, $private_bonissim_account);
        self::assertEquals(80, $private_bonissim_account[0]->redeemable_amount);


    }

    function testRechargeAndBonificationV2(){
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $user = $this->getSignedInUser();

        $accounts = $user->accounts;
        self::assertEquals(100000000000, $accounts[0]->wallets[0]->balance);
        self::assertEquals(10000000000, $accounts[1]->wallets[0]->balance);
        self::assertEquals(100000000000, $accounts[2]->wallets[0]->balance);

        $this->createLemonTxV3(6000);

        $lastTxsResp = $this->requestJson('GET', '/user/v1/last');
        $lastTxsContent = json_decode($lastTxsResp->getContent(), true);
        $lastTxs = $lastTxsContent['data'];
        $isBonification = false;
        foreach ($lastTxs as $lastTx){
            if($lastTx['is_bonification'] === true){
                $isBonification = true;
            }
        }

        self::assertTrue($isBonification);

        //check wallet to have 6 more recs
        $user = $this->getSignedInUser();

        $accounts = $user->accounts;

        self::assertEquals(100600000000, $accounts[0]->wallets[0]->balance);
        self::assertEquals(10000000000, $accounts[1]->wallets[0]->balance);
        self::assertEquals(100000000000, $accounts[2]->wallets[0]->balance);

        //new transaction to exceed max
        $this->createLemonTxV3(100000);

        //check wallet to receive max (it should receive 100 but only will receive 94 because of previous tx)
        $user = $this->getSignedInUser();

        $accounts = $user->accounts;

        self::assertEquals(110000000000, $accounts[0]->wallets[0]->balance);
        self::assertEquals(10000000000, $accounts[1]->wallets[0]->balance);
        self::assertEquals(100000000000, $accounts[2]->wallets[0]->balance);

        //new transaction with max bonificatino exceeded,shouldnt bnificate anything
        $this->createLemonTxV3(10000);

        //check wallet to not receive anything, bonification max exceeded for this account
        $user = $this->getSignedInUser();

        $accounts = $user->accounts;

        self::assertEquals(110000000000, $accounts[0]->wallets[0]->balance);
        self::assertEquals(10000000000, $accounts[1]->wallets[0]->balance);
        self::assertEquals(100000000000, $accounts[2]->wallets[0]->balance);

        //at this point we have 100 recs acumulated

        //TODO pay to user to not allowed to spend bonifications
        $account = $this->getSinglePrivateAccount();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);

        $route = "/methods/v3/out/".$this->getCryptoMethod();
        $amount = 110000000000;
        $resp = $this->rest(
            'POST',
            $route,
            [
                'address' => $account->rec_address,
                'amount' => $amount,
                'concept' => 'Testing concept',
                'pin' => UserFixture::TEST_USER_CREDENTIALS['pin']
            ],
            [],
            400
        );

        self::assertEquals('error', $resp->status);
        self::assertEquals('Not funds enough. You can not use bonused balance in a private transaction', $resp->message);

        //pay to store to spend acumulated bonifications
        $store = $this->getSingleStore();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);

        $route = "/methods/v3/out/".$this->getCryptoMethod();
        $amount = 110000000000;
        $resp = $this->rest(
            'POST',
            $route,
            [
                'address' => $store->rec_address,
                'amount' => $amount,
                'concept' => 'Testing concept',
                'pin' => UserFixture::TEST_USER_CREDENTIALS['pin']
            ],
            [],
            201
        );

        self::assertEquals('success', $resp->status);
        self::assertEquals('Done', $resp->message);
    }

    private function getSingleStore(){
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        //UserFixture::TEST_ADMIN_CREDENTIALS es el owner de esta tienda
        $store = $this->rest('GET', '/admin/v3/accounts?type=COMPANY')[0];
        $this->signOut();
        return $store;
    }

    private function getSinglePrivateAccount(){
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $account = $this->rest('GET', '/admin/v3/accounts?type=PRIVATE')[0];
        $this->signOut();
        return $account;
    }

    /**
     * @param array $data
     */
    private function useLemonWayMock(array $data): void
    {
        $lw = $this->createMock(LemonWayMethod::class);
        $lw->method('getCurrency')->willReturn("EUR");
        $lw->method('getPayInInfoWithCommerce')->willReturn($data);
        $lw->method('getCname')->willReturn('lemonway');
        $lw->method('getType')->willReturn('in');
        $lw->method('getPayInStatus')->willReturn($data);

        $this->inject('net.app.in.lemonway.v1', $lw);
    }

    /**
     * @param array $data
     * @throws \Exception
     */
    private function executeRecharge(array $data): void
    {
        $route = '/methods/v3/in/lemonway';
        $resp = $this->requestJson('POST', $route, $data);
        $content_post = json_decode($resp->getContent(), true);

        $this->runCommand('rec:fiatV3:check');
        $this->runCommand('rec:crypto:check');
        $this->runCommand('rec:crypto:check');
    }

    /**
     * @param array $data
     * @throws \Exception
     */
    private function executeRechargeV3(array $data): void
    {
        $route = '/methods/v3/in/lemonway';
        $resp = $this->requestJson('POST', $route, $data);
        $content_post = json_decode($resp->getContent(), true);

        $this->runCommand('rec:fiatV3:check');
        //$this->runCommand('rec:crypto:check');
        //$this->runCommand('rec:crypto:check');
    }

    function testRechargeCultureAccountShuldSend50(){

        $transaccion_amount = 200;

        // create culture account
        $campaign = $this->rest('GET', "/admin/v3/campaigns?name=".Campaign::CULTURE_CAMPAIGN_NAME)[0];
        self::assertTrue(isset($campaign));
        $user = json_decode($this->requestJson('GET', '/admin/v3/user/1')->getContent(), true);
        self::assertFalse($user['data'][$campaign->tos]);
        $resp = $this->requestJson('PUT', '/user/v4/campaign/accept_tos', ["campaign_code" => $campaign->code]);
        self::assertEquals(204, $resp->getStatusCode());
        $user_id = $user["data"]["id"];

        $private_culture_accounts = $this->rest('GET', "/user/v3/accounts?campaigns=2&type=PRIVATE&kyc_manager=".$user_id);
        self::assertCount(1, $private_culture_accounts);

        $private_culture_account_id = $private_culture_accounts[0]->id;

        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        $user_pin = $em->getRepository(User::class)->findOneBy(['id' => $user_id])->getPin();

        $culture_commerce_account = $this->rest('GET', "/user/v3/groups/search?name=".AccountFixture::TEST_ACCOUNT_CULT21_COMMERCE['name']);
        self::assertEquals(sizeof($culture_commerce_account), 1);

        $data = ['status' => Transaction::$STATUS_RECEIVED,
            'company_id' => $private_culture_account_id,
            'amount' => $transaccion_amount * 100, // 2 decimals
            'commerce_id' => $culture_commerce_account[0]->id,
            'concept' => 'test recharge',
            'pin' => $user_pin,
            'save_card' => 0];


        //update mock

        $this->useLemonWayMock($data);
        //make first recharge
        $this->executeRecharge($data);
        $private_culture_accounts = $this->rest('GET', "/user/v3/accounts?id=".$private_culture_account_id);


        self::assertEquals(250, $private_culture_accounts[0]->wallets[0]->balance / 1e8);

        // limits
        $this->sendFromCultureAccountToNoCultureAccountShouldFail($private_culture_accounts[0]);
        $this->receiveFromCultureAccountToNoCultureAccountShouldFail($private_culture_accounts[0]);

        //TODO get last transactions and find one with required concept bonification
        $signedUser = $this->getSignedInUser();
        $this->rest('PUT', '/user/v1/activegroup', ['group_id' => $private_culture_accounts[0]->id]);
        $txs = $this->rest('GET','/user/v2/wallet/transactions');
        $conceptCulturaBonification = 'Bonificació Cultural +50%';
        $existCulturaBonification = false;
        foreach ($txs as $tx){
            if($tx->type === 'in'){
                $concept = $tx->pay_in_info->concept;
                if($concept === $conceptCulturaBonification) {
                    $existCulturaBonification = true;
                }
            }
        }

        self::assertTrue($existCulturaBonification);

    }

    function testRechargeCultureAccountShuldSend50AndCheckConcepts(){

        $transaccion_amount = 200;

        $campaign = $this->rest('GET', "/admin/v3/campaigns?name=".Campaign::CULTURE_CAMPAIGN_NAME)[0];
        self::assertTrue(isset($campaign));

        $user = json_decode($this->requestJson('GET', '/admin/v3/user/1')->getContent(), true);
        self::assertFalse($user['data'][$campaign->tos]);

        $resp = $this->requestJson('PUT', '/user/v4/campaign/accept_tos', ["campaign_code" => $campaign->code]);
        self::assertEquals(204, $resp->getStatusCode());
        $user_id = $user["data"]["id"];

        $private_culture_accounts = $this->rest('GET', "/user/v3/accounts?campaigns=2&type=PRIVATE&kyc_manager=".$user_id);
        self::assertCount(1, $private_culture_accounts);

        $private_culture_account_id = $private_culture_accounts[0]->id;

        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        $user_pin = $em->getRepository(User::class)->findOneBy(['id' => $user_id])->getPin();

        $culture_commerce_account = $this->rest('GET', "/user/v3/groups/search?name=".AccountFixture::TEST_ACCOUNT_CULT21_COMMERCE['name']);
        self::assertEquals(sizeof($culture_commerce_account), 1);

        $data = ['status' => Transaction::$STATUS_RECEIVED,
            'company_id' => $private_culture_account_id,
            'amount' => $transaccion_amount * 100, // 2 decimals
            'commerce_id' => $culture_commerce_account[0]->id,
            'concept' => 'test recharge',
            'pin' => $user_pin,
            'save_card' => 0];


        //update mock

        $this->useLemonWayMock($data);
        //make first recharge
        $this->executeRecharge($data);
        $private_culture_accounts = $this->rest('GET', "/user/v3/accounts?id=".$private_culture_account_id);

        self::assertEquals(250, $private_culture_accounts[0]->wallets[0]->balance / 1e8);
        self::assertEquals(100, $private_culture_accounts[0]->rewarded_amount);

        // limits
        $this->sendFromCultureAccountToNoCultureAccountShouldFail($private_culture_accounts[0]);
        $this->receiveFromCultureAccountToNoCultureAccountShouldFail($private_culture_accounts[0]);



    }

    /**
     * @param $private_culture_accounts
     */
    private function sendFromCultureAccountToNoCultureAccountShouldFail($private_culture_accounts): void
    {
        $commerces = $this->rest('GET', "/user/v3/accounts?name=COMMERCEACCOUNT");
        self::assertGreaterThanOrEqual(1, count($commerces));

        foreach ($commerces as $commerce) {
            if ($commerce->name != Campaign::CULTURE_CAMPAIGN_NAME) {
                $not_culture_account = $commerce;
            }
        }
        self::assertTrue(isset($not_culture_account));
        $init_balance = $private_culture_accounts->wallets[0]->balance;

        // changing the active account for the current user
        $this->rest('PUT', '/user/v1/activegroup', ['group_id' => $private_culture_accounts->id]);

        //pay to commerce
        $resp = $this->rest(
            'POST',
            '/methods/v3/out/'.$this->getCryptoMethod(),
            [
                'address' => $not_culture_account->rec_address,
                'amount' => 1e8,
                'concept' => 'Testing concept',
                'pin' => UserFixture::TEST_ADMIN_CREDENTIALS['pin']
            ],
            [],
            400
        );
        $not_culture_account = $this->rest('GET', "/user/v3/groups/search?id=".$not_culture_account->id);
        self::assertEquals(sizeof($not_culture_account), 1);

        $this->runCommand('rec:crypto:check');
        $this->runCommand('rec:crypto:check');

        $userInfo = json_decode($this->requestJson('GET', "/user/v1/account")->getContent(),true);
        $fin_balance = -1;
        foreach ($userInfo["data"]["accounts"] as $account) {
            if ($account["id"] == $private_culture_accounts->id) {
                $fin_balance = $account["wallets"][0]["balance"];
            }
        }
        self::assertEquals($init_balance, $fin_balance);

    }

    /**
     * @param $private_culture_accounts
     */
    private function receiveFromCultureAccountToNoCultureAccountShouldFail($private_culture_account): void
    {
        $no_culture_commerce_account = $this->rest('GET', "/user/v3/groups/search?name=".AccountFixture::TEST_ACCOUNT_COMMERCE['name']);
        self::assertEquals(sizeof($no_culture_commerce_account), 1);

        $init_balance = $private_culture_account->wallets[0]->balance;

        // changing the active account for the current user
        $this->rest('PUT', '/user/v1/activegroup', ['group_id' => $no_culture_commerce_account[0]->id]);



        //pay to commerce
        $resp = $this->rest(
            'POST',
            '/methods/v3/out/'.$this->getCryptoMethod(),
            [
                'address' => $private_culture_account->rec_address,
                'amount' => 1e8,
                'concept' => 'Testing concept',
                'pin' => UserFixture::TEST_ADMIN_CREDENTIALS['pin']
            ],
            [],
            400
        );

        $this->runCommand('rec:crypto:check');
        $this->runCommand('rec:crypto:check');

        $userInfo = json_decode($this->requestJson('GET', "/user/v1/account")->getContent(),true);
        $fin_balance = -1;
        foreach ($userInfo["data"]["accounts"] as $account) {
            if ($account["id"] == $private_culture_account->id) {
                $fin_balance = $account["wallets"][0]["balance"];
            }
        }
        self::assertEquals($init_balance, $fin_balance);
    }

    private function createLemonTxV3($amount){
        $user = $this->getSignedInUser();
        $data = ['status' => Transaction::$STATUS_RECEIVED,
            'company_id' => $user->group_data->id,
            'amount' => $amount,
            'commerce_id' => 6,
            'concept' => 'test recharge v2 bonification',
            'pin' => UserFixture::TEST_USER_CREDENTIALS['pin'],
            'save_card' => 0];

        $this->useLemonWayMock($data);

        $this->executeRechargeV3($data);
    }
}
