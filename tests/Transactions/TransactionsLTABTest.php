<?php

namespace App\Tests\Transactions;

use App\DataFixtures\AccountFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\Campaign;
use App\Entity\Group;
use App\Entity\User;
use App\Entity\UserWallet;
use App\Tests\BaseApiTest;

/**
 * Class TransactionsTest
 * @package App\Tests\Transactions
 * @group mongo
 */
class TransactionsLTABTest extends BaseApiTest {

    private $store;

    function setUp(): void
    {
        parent::setUp();
        $this->store = $this->getSingleStore();
        $this->setClientIp($this->faker->ipv4);

    }


    private function getSingleStore(){
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
        $store = $this->rest('GET', '/admin/v3/accounts?name='.AccountFixtures::TEST_ACCOUNT_LTAB_COMMERCE['name'])[0];
        $this->signOut();
        return $store;
    }

    function testPay10RecToLTABStoreShouldWork(){
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');

        //private account
        /** @var Group $targetAccount */
        $targetAccount = $em->getRepository(Group::class)->findOneBy(['name' => AccountFixtures::TEST_ACCOUNT_LTAB_PRIVATE['name'].'_private']);

        //set redeemable amount to all private companies in LTAB campaign
        $ltabAccounts = $em->getRepository(Group::class)->findBy(['type' => Group::ACCOUNT_TYPE_PRIVATE, 'name' => Campaign::BONISSIM_CAMPAIGN_NAME]);
        foreach ($ltabAccounts as $ltabAccount){
            $ltabAccount->setRedeemableAmount(1000e8);
            $em->flush();
        }

        //set active group private account
        /** @var User $targetUser */
        $targetUser = $em->getRepository(User::class)->findOneBy(['username' => UserFixtures::TEST_USER_LTAB_CREDENTIALS['username']]);
        $targetUser->setActiveGroup($targetAccount);
        $em->flush();

        $this->signIn(UserFixtures::TEST_USER_LTAB_CREDENTIALS);

        $accounts = $this->getSignedInUser()->accounts;
        self::assertEquals(100000000000, $accounts[0]->wallets[0]->balance);
        self::assertEquals(100000000000, $accounts[1]->wallets[0]->balance);

        $route = "/methods/v1/out/".$this->getCryptoMethod();
        $resp = $this->rest(
            'POST',
            $route,
            [
                'address' => $this->store->rec_address,
                'amount' => 10e8,
                'concept' => 'Testing LTAB',
                'pin' => UserFixtures::TEST_USER_LTAB_CREDENTIALS['pin']
            ],
            [],
            201
        );

        //run command to apply bonifications
        $output = $this->runCommand("rec:crypto:check");
        self::assertNotEmpty($output);

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

        //we need to execute the command twuce because it seems like all the tx are generated in two steps
        $output = $this->runCommand("rec:crypto:check");
        self::assertNotEmpty($output);

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

        //TODO check rede

        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);

        $txRoute = "/admin/v1/transaction/list?sort=sender_id&order=desc'";
        $txs = $this->rest(
            'GET',
            $txRoute,
            [],
            [],
            200
        );

        //self::assertGreaterThan($txs->list[sizeof($txs->list) - 1][1], $txs->list[0][1]);
    }


    function testPay10RecToYourOwnLTABStoreShouldNotBonifyTx(){
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');

        //private account
        /** @var Group $targetAccount */
        $targetAccount = $em->getRepository(Group::class)->findOneBy(['name' => AccountFixtures::TEST_ACCOUNT_LTAB_PRIVATE['name'].'_private']);

        //set redeemable amount to all private companies in LTAB campaign
        $ltabAccounts = $em->getRepository(Group::class)->findBy(['type' => Group::ACCOUNT_TYPE_PRIVATE, 'name' => Campaign::BONISSIM_CAMPAIGN_NAME]);
        foreach ($ltabAccounts as $ltabAccount){
            $ltabAccount->setRedeemableAmount(1000e8);
            $em->flush();
        }

        //set active group private account
        /** @var User $targetUser */
        $targetUser = $em->getRepository(User::class)->findOneBy(['username' => UserFixtures::TEST_USER_LTAB_CREDENTIALS['username']]);
        $targetUser->setActiveGroup($targetAccount);
        $em->flush();

        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
        $store = $this->rest('GET', '/admin/v3/accounts?name='.AccountFixtures::TEST_ACCOUNT_LTAB_PRIVATE['name'].'_store')[0];

        $this->signIn(UserFixtures::TEST_USER_LTAB_CREDENTIALS);

        $route = "/methods/v1/out/".$this->getCryptoMethod();
        $resp = $this->rest(
            'POST',
            $route,
            [
                'address' => $store->rec_address,
                'amount' => 10e8,
                'concept' => 'Testing LTAB',
                'pin' => UserFixtures::TEST_USER_LTAB_CREDENTIALS['pin']
            ],
            [],
            201
        );

        //run command to apply bonifications
        $output = $this->runCommand("rec:crypto:check");
        self::assertNotEmpty($output);

        //we need to execute the command twuce because it seems like all the tx are generated in two steps
        $output = $this->runCommand("rec:crypto:check");
        self::assertNotEmpty($output);

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

    function testPay10RecToLTABStoreShouldWith1RecInCampaignAccount()
    {
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');

        //set campaign account balance to 1Rec
        /** @var Campaign $campaign */
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => Campaign::BONISSIM_CAMPAIGN_NAME]);
        $campaign_account = $em->getRepository(Group::class)->find($campaign->getCampaignAccount());
        $wallet = $campaign_account->getWallets()[0];
        $wallet->setBalance(1e8);
        $wallet->setAvailable(1e8);
        $em->flush();
        $wallet = $em->getRepository(UserWallet::class)->find($wallet->getId());
        self::assertEquals(1e8, $wallet->getBalance());

        //private account
        /** @var Group $targetAccount */
        $targetAccount = $em->getRepository(Group::class)->findOneBy(['name' => AccountFixtures::TEST_ACCOUNT_LTAB_PRIVATE['name'] . '_private']);

        //set redeemable amount to all private companies in LTAB campaign
        $ltabAccounts = $em->getRepository(Group::class)->findBy(['type' => Group::ACCOUNT_TYPE_PRIVATE, 'name' => Campaign::BONISSIM_CAMPAIGN_NAME]);
        foreach ($ltabAccounts as $ltabAccount) {
            $ltabAccount->setRedeemableAmount(1000e8);
            $em->flush();
        }

        //set active group private account
        /** @var User $targetUser */
        $targetUser = $em->getRepository(User::class)->findOneBy(['username' => UserFixtures::TEST_USER_LTAB_CREDENTIALS['username']]);
        $targetUser->setActiveGroup($targetAccount);
        $em->flush();

        $this->signIn(UserFixtures::TEST_USER_LTAB_CREDENTIALS);

        $accounts = $this->getSignedInUser()->accounts;
        self::assertEquals(100000000000, $accounts[0]->wallets[0]->balance);
        self::assertEquals(100000000000, $accounts[1]->wallets[0]->balance);




        $route = "/methods/v1/out/".$this->getCryptoMethod();
        $resp = $this->rest(
            'POST',
            $route,
            [
                'address' => $this->store->rec_address,
                'amount' => 10e8,
                'concept' => 'Testing LTAB',
                'pin' => UserFixtures::TEST_USER_LTAB_CREDENTIALS['pin']
            ],
            [],
            201
        );

        //run command to apply bonifications
        $output = $this->runCommand("rec:crypto:check");
        self::assertNotEmpty($output);

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

        //we need to execute the command twuce because it seems like all the tx are generated in two steps
        $output = $this->runCommand("rec:crypto:check");
        self::assertNotEmpty($output);

        $accountRoute = "/user/v1/account";
        //generate LTAB transaction
        $account = $this->rest(
            'GET',
            $accountRoute,
            [],
            [],
            200
        );

        //check account balance to see bonification (only 1Rec)
        $accounts = $account->accounts;

        self::assertEquals(99000000000, $accounts[0]->wallets[0]->balance);
        self::assertEquals(100100000000, $accounts[1]->wallets[0]->balance);
    }

}
