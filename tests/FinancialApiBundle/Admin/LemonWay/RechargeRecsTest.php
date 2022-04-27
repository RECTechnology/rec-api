<?php

namespace Test\FinancialApiBundle\Admin\LemonWay;

use App\FinancialApiBundle\DataFixture\AccountFixture;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\Tier;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Financial\Methods\LemonWayMethod;
use Test\FinancialApiBundle\Admin\AdminApiTest;

/**
 * Class RechargeRecsTest
 * @package Test\FinancialApiBundle\Admin\LemonWay
 * @group mongo
 */
class RechargeRecsTest extends AdminApiTest {

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
        //this is not used
        if (count($private_bonissim_account) > 0){
            $initial_balance = $private_bonissim_account[0]->wallets[0]->balance;
        }else{
            $initial_balance = 0;
        }

        $private_accounts = $this->rest('GET', "/user/v3/accounts?type=PRIVATE&kyc_manager=".$user_id);
        self::assertGreaterThanOrEqual(1, count($private_accounts));
        $private_account_id = $private_accounts[0]->id;

        $acc = $em->getRepository(Group::class)->find($private_account_id);
        $acc->setCompanyImage('');
        $em->persist($acc);
        $em->flush();

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
        $route = '/methods/v1/in/lemonway';
        $resp = $this->requestJson('POST', $route, $data);
        $content_post = json_decode($resp->getContent(), true);

        $this->runCommand('rec:fiat:check');
        $this->runCommand('rec:crypto:check');
        $this->runCommand('rec:crypto:check');
    }

    function testRechargeCultureAndGetReward(){

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
            '/methods/v1/out/rec',
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
            '/methods/v1/out/rec',
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

    function testDissableRedeemable()
    {
        //this test only pass if parameter group_root_id = 1
        $user_id = 1;
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        $user_pin = $em->getRepository(User::class)->findOneBy(['id' => $user_id])->getPin();

        $route = "/admin/v3/user/{$user_id}";
        $resp = $this->rest('PUT', $route, ['private_tos_campaign' => true]);
        self::assertTrue($resp->private_tos_campaign);

        $private_bonissim_account = $this->rest('GET', "/user/v3/accounts?campaigns=1&type=PRIVATE&kyc_manager=" . $user_id);
        self::assertCount(0, $private_bonissim_account);

        $campaign = $this->rest('GET', "/admin/v3/campaigns?name=" . Campaign::BONISSIM_CAMPAIGN_NAME)[0];
        self::assertTrue(isset($campaign));

        $private_accounts = $this->rest('GET', "/user/v3/accounts?type=PRIVATE&kyc_manager=" . $user_id);
        self::assertGreaterThanOrEqual(1, count($private_accounts));
        $private_account_id = $private_accounts[0]->id;

        $acc = $em->getRepository(Group::class)->find($private_account_id);
        $acc->setCompanyImage('');
        $em->persist($acc);
        $em->flush();

        $company_accounts = $this->rest('GET', "/user/v3/accounts?type=COMPANY&kyc_manager=" . $user_id);

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

        $this->whenDissabledBonificationNoLtabAccountCreated($campaign, $data, $user_id);
        $this->whenEabledBonificationLtabAccountCreatedAndRedeemable($campaign, $data, $user_id);
        $this->whenDissabledBonificationNoRedeemable($campaign, $data, $user_id);

    }

    function testSetExchanger()
    {
        $user_id = 2;
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        $user_pin = $em->getRepository(User::class)->findOneBy(['id' => $user_id])->getPin();

        $private_accounts = $this->rest('GET', "/user/v3/accounts?type=PRIVATE&kyc_manager=" . $user_id);
        self::assertGreaterThanOrEqual(1, count($private_accounts));
        $private_account_id = $private_accounts[0]->id;

        $company_accounts = $this->rest('GET', "/user/v3/accounts?type=COMPANY");
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


        $kyc0_id = $this->rest('GET', "/user/v3/tier?code=KYC0")[0]->id;
        $kyc2_id = $this->rest('GET', "/user/v3/tier?code=KYC2")[0]->id;

        $this->setExchangerFunctionTest();

        $this->rechargeWhenAllCommerceHasKYC0ShouldFail($company_accounts, $kyc0_id, $data);
        $this->rechargeWhenAllCommerceHasKYCNullShouldFail($data);
        $this->rechargeWhenAllCommerceHasKYC0AndGroupRootHasKYC2ShouldFail($kyc2_id, $data);

    }

    /**
     * @param $campaign
     * @param array $data
     * @param int $user_id
     * @throws \Exception
     */
    private function whenDissabledBonificationNoLtabAccountCreated($campaign, array $data, int $user_id): void
    {
        // dissable bonification
        $resp = $this->rest('PUT', "/admin/v3/campaign/" . $campaign->id, ['bonus_enabled' => false]);
        self::assertFalse($resp->bonus_enabled);

        $this->executeRecharge($data);

        //check create bonissim account
        $private_bonissim_account = $this->rest('GET', "/user/v3/accounts?campaigns=1&type=PRIVATE&kyc_manager=" . $user_id);
        self::assertCount(0, $private_bonissim_account);
    }

    /**
     * @param $campaign
     * @param array $data
     * @param int $user_id
     * @throws \Exception
     */
    private function whenEabledBonificationLtabAccountCreatedAndRedeemable($campaign, array $data, int $user_id): void
    {
        // enable bonification
        $resp = $this->rest('PUT', "/admin/v3/campaign/" . $campaign->id, ['bonus_enabled' => true]);
        self::assertTrue($resp->bonus_enabled);
        $this->executeRecharge($data);

        //check create bonissim account
        $private_bonissim_account = $this->rest('GET', "/user/v3/accounts?campaigns=1&type=PRIVATE&kyc_manager=" . $user_id);
        self::assertCount(1, $private_bonissim_account);
        self::assertEquals(60, $private_bonissim_account[0]->redeemable_amount);
    }

    /**
     * @param $campaign
     * @param array $data
     * @param int $user_id
     * @throws \Exception
     */
    private function whenDissabledBonificationNoRedeemable($campaign, array $data, int $user_id): void
    {
        // dissable bonification
        $resp = $this->rest('PUT', "/admin/v3/campaign/" . $campaign->id, ['bonus_enabled' => false]);
        self::assertFalse($resp->bonus_enabled);

        $this->executeRecharge($data);

        //check create bonissim account
        $private_bonissim_account = $this->rest('GET', "/user/v3/accounts?campaigns=1&type=PRIVATE&kyc_manager=" . $user_id);
        self::assertCount(1, $private_bonissim_account);
        //no redeemable added
        self::assertEquals(60, $private_bonissim_account[0]->redeemable_amount);
    }

    /**
     * @param array $company_accounts
     * @param $kyc0_id
     * @param array $data
     */
    private function rechargeWhenAllCommerceHasKYC0ShouldFail(array $company_accounts, $kyc0_id, array $data): void
    {
        foreach ($company_accounts as $account) {
            $route = "/admin/v3/accounts/{$account->id}";
            $this->rest('PUT', $route, ['level_id' => $kyc0_id]);
        }

        $resp = $this->rest(
            'POST',
            '/methods/v1/in/lemonway',
            $data,
            [],
            403
        );
    }

    /**
     * @param array $company_accounts
     * @param $kyc0_id
     * @param array $data
     */
    private function rechargeWhenAllCommerceHasKYCNullShouldFail(array $data): void
    {
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        $company_accounts = $em->getRepository(Group::class)->findBy(array("type" => "COMPANY"));
        /** @var Group $account */
        foreach ($company_accounts as $account) {
            $account->setLevel(null);
            $em->flush();
        }

        $resp = $this->rest(
            'POST',
            '/methods/v1/in/lemonway',
            $data,
            [],
            403
        );
    }

    /**
     * @param $kyc2_id
     * @param array $data
     */
    private function rechargeWhenAllCommerceHasKYC0AndGroupRootHasKYC2ShouldFail($kyc2_id, array $data): void
    {
        $group_root_id = self::createClient()->getContainer()->getParameter('id_group_root');
        $route = "/admin/v3/accounts/{$group_root_id}";
        $this->rest('PUT', $route, ['level_id' => $kyc2_id]);

        $resp = $this->rest(
            'POST',
            '/methods/v1/in/lemonway',
            $data,
            [],
            403
        );
    }

    //demonstrates old Tier bug
    function testRechargeWhenAllCommerceHasKYC2AndTier0ShouldPass(): void
    {

        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->getRepository(User::class)->findOneBy(['username' => UserFixture::TEST_USER_CREDENTIALS['username']]);
        $user_id = $user->getId();
        $user_pin = $em->getRepository(User::class)->findOneBy(['id' => $user_id])->getPin();

        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $private_accounts = $this->rest('GET', "/user/v3/accounts?type=PRIVATE&kyc_manager=" . $user_id);
        self::assertGreaterThanOrEqual(1, count($private_accounts));
        $private_account_id = $private_accounts[0]->id;

        $company_accounts = $this->rest('GET', "/user/v3/accounts?type=COMPANY");
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

        $kyc2_id = $this->rest('GET', "/user/v3/tier?code=KYC2")[0]->id;
        foreach ($company_accounts as $account) {
            $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
            $route = "/admin/v3/accounts/{$account->id}";
            $this->rest('PUT', $route, ['level_id' => $kyc2_id, 'tier' => 0]);
        }
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $resp = $this->rest(
            'POST',
            '/methods/v1/in/lemonway',
            $data,
            [],
            201
        );
    }

    function setExchangerFunctionTest(){
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        //PUT at least one company with level null
        $company_accounts = $em->getRepository(Group::class)->findBy(array("type" => "COMPANY"));
        $company_accounts[0]->setLevel(null);
        $em->flush();

        $kyc2 = $em->getRepository(Tier::class)->findOneBy(array('code' => 'KYC2'));
        $exchangers = $em->getRepository(Group::class)->findBy([
            'type' => 'COMPANY',
            'level' => $kyc2->getId(),
            'active' => 1]);

        foreach ($exchangers as $exchanger){
            self::assertNotNull($exchanger->getLevel());
        }
    }
}
