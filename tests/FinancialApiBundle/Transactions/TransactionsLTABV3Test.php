<?php

namespace Test\FinancialApiBundle\Transactions;

use App\FinancialApiBundle\DataFixture\AccountFixture;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\User;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class TransactionsLTABV3Test
 * @package Test\FinancialApiBundle\Transactions
 * @group mongo
 */
class TransactionsLTABV3Test extends BaseApiTest {

    private $store;

    function setUp(): void
    {
        parent::setUp();
        $this->store = $this->getSingleStore();
        $this->setClientIp($this->faker->ipv4);

    }


    private function getSingleStore(){
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $store = $this->rest('GET', '/admin/v3/accounts?name='.AccountFixture::TEST_ACCOUNT_LTAB_COMMERCE['name'])[0];
        $this->signOut();
        return $store;
    }

    function testPay10RecToLTABStoreShouldWork(){
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');

        //private account
        /** @var Group $targetAccount */
        $targetAccount = $em->getRepository(Group::class)->findOneBy(['name' => AccountFixture::TEST_ACCOUNT_LTAB_PRIVATE['name'].'_private']);

        //set redeemable amount to all private companies in LTAB campaign
        $ltabAccounts = $em->getRepository(Group::class)->findBy(['type' => Group::ACCOUNT_TYPE_PRIVATE, 'name' => Campaign::BONISSIM_CAMPAIGN_NAME]);
        foreach ($ltabAccounts as $ltabAccount){
            $ltabAccount->setRedeemableAmount(1000e8);
            $em->flush();
        }

        //set active group private account
        /** @var User $targetUser */
        $targetUser = $em->getRepository(User::class)->findOneBy(['username' => UserFixture::TEST_USER_LTAB_CREDENTIALS['username']]);
        $targetUser->setActiveGroup($targetAccount);
        $em->flush();

        $this->signIn(UserFixture::TEST_USER_LTAB_CREDENTIALS);

        $accounts = $this->getSignedInUser()->accounts;
        self::assertEquals(100000000000, $accounts[0]->wallets[0]->balance);
        self::assertEquals(100000000000, $accounts[1]->wallets[0]->balance);

        $route = "/methods/v3/out/rec";
        $resp = $this->rest(
            'POST',
            $route,
            [
                'address' => $this->store->rec_address,
                'amount' => 10e8,
                'concept' => 'Testing LTAB',
                'pin' => UserFixture::TEST_USER_LTAB_CREDENTIALS['pin']
            ],
            [],
            201
        );
        self::assertEquals(10e8 * 0.15, $resp->extra_data->rewarded_ltab);

        $accountRoute = "/user/v1/account";
        //generate LTAB transaction
        $account = $this->rest(
            'GET',
            $accountRoute,
            [],
            [],
            200
        );

        //check account balance to see bonification
        $accounts = $account->accounts;

        self::assertEquals(99000000000, $accounts[0]->wallets[0]->balance);
        self::assertEquals(100150000000, $accounts[1]->wallets[0]->balance);

    }

    function testPay10RecToYourOwnLTABStoreShouldNotBonifyTx(){
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');

        //private account
        /** @var Group $targetAccount */
        $targetAccount = $em->getRepository(Group::class)->findOneBy(['name' => AccountFixture::TEST_ACCOUNT_LTAB_PRIVATE['name'].'_private']);

        //set redeemable amount to all private companies in LTAB campaign
        $ltabAccounts = $em->getRepository(Group::class)->findBy(['type' => Group::ACCOUNT_TYPE_PRIVATE, 'name' => Campaign::BONISSIM_CAMPAIGN_NAME]);
        foreach ($ltabAccounts as $ltabAccount){
            $ltabAccount->setRedeemableAmount(1000e8);
            $em->flush();
        }

        //set active group private account
        /** @var User $targetUser */
        $targetUser = $em->getRepository(User::class)->findOneBy(['username' => UserFixture::TEST_USER_LTAB_CREDENTIALS['username']]);
        $targetUser->setActiveGroup($targetAccount);
        $em->flush();

        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $store = $this->rest('GET', '/admin/v3/accounts?name='.AccountFixture::TEST_ACCOUNT_LTAB_PRIVATE['name'].'_store')[0];

        $this->signIn(UserFixture::TEST_USER_LTAB_CREDENTIALS);

        $route = "/methods/v3/out/rec";
        $resp = $this->rest(
            'POST',
            $route,
            [
                'address' => $store->rec_address,
                'amount' => 10e8,
                'concept' => 'Testing LTAB',
                'pin' => UserFixture::TEST_USER_LTAB_CREDENTIALS['pin']
            ],
            [],
            201
        );

        $accountRoute = "/user/v1/account";
        //generate LTAB transaction
        $account = $this->rest(
            'GET',
            $accountRoute,
            [],
            [],
            200
        );

        //check account balance to see bonification
        $accounts = $account->accounts;

        self::assertEquals(99000000000, $accounts[0]->wallets[0]->balance);
        self::assertEquals(100000000000, $accounts[1]->wallets[0]->balance);
        self::assertEquals(100000000000, $accounts[1]->redeemable_amount);
        self::assertEquals(0, $accounts[1]->rewarded_amount);

    }

    //TODO esta parte no toca nada de la tpv, deberiamos ponerla en otro sitio

    function testLTABAccountPaysToLTABCommerceShouldSuccess(){
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
                    '/methods/v3/out/rec',
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

    function testLTABAccountPaysToNoLTABCommerceShouldFail(){
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
                    '/methods/v3/out/rec',
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

    function testAccountPaysToLTABCommerceShouldSend15P(){
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
            '/methods/v3/out/rec',
            [
                'address' => $bonissim_company_account->rec_address,
                'amount' => $tx_amount * 1e8,
                'concept' => 'Testing concept',
                'pin' => UserFixture::TEST_USER_LTAB_CREDENTIALS['pin']
            ]
        );

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
        self::assertMatchesRegularExpression("/Processing/", $output);
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

    function testAccountPaysToNoLTABCommerceShouldReduceRedeemable()
    {
        $this->setClientIp($this->faker->ipv4);


        $accounts = $this->getAsAdmin("/admin/v3/accounts?limit=50");
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
            '/methods/v3/out/rec',
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
                !isset($account->campaigns[0])){
                $usar_total_balance += $account->wallets[0]->available;
            }
        }
        $max_redeemable = max($usar_total_balance  / 1e8 - $tx_amount, 0);
        self::assertEquals($max_redeemable, $_bonissim_private_account->redeemable_amount);
    }



    function testSendFromNoCultureAccountToCultureAccountShouldFail(): void

    {
        $this->setClientIp($this->faker->ipv4);
        $reciver = $this->getAsAdmin('/admin/v3/accounts?name='.AccountFixture::TEST_ACCOUNT_CULT21_COMMERCE['name'])[0];
        $sender = $this->getAsAdmin('/admin/v3/accounts?name='.UserFixture::TEST_THIRD_USER_CREDENTIALS['name'])[0];
        $start_balance = $sender->wallets[0]->balance;
        $this->signIn(UserFixture::TEST_THIRD_USER_CREDENTIALS);
        echo'';
        $resp = $this->rest(
            'POST',
            '/methods/v3/out/rec',
            [
                'address' => $reciver->rec_address,
                'amount' => 10e8,
                'concept' => 'Testing concept',
                'pin' => UserFixture::TEST_THIRD_USER_CREDENTIALS['pin']
            ],
            [],
            400
        );

        $_sender = $this->getAsAdmin('/admin/v3/accounts?name='.UserFixture::TEST_THIRD_USER_CREDENTIALS['name'])[0];
        $end_balance = $_sender->wallets[0]->balance;
        self::assertEquals($start_balance, $end_balance);


    }

    function testPayKycCheck(): void
    {
        $this->setClientIp($this->faker->ipv4);
        $reciver = $this->getAsAdmin('/admin/v3/accounts?level=2')[1];

        $resp = $this->rest(
            'POST',
            '/methods/v3/out/rec',
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

}
