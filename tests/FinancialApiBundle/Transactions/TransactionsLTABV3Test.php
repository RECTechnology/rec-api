<?php

namespace Test\FinancialApiBundle\Transactions;

use App\FinancialApiBundle\Controller\Google2FA;
use App\FinancialApiBundle\DataFixture\AccountFixture;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\User;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\Utils\MongoDBTrait;

/**
 * Class TransactionsLTABV3Test
 * @package Test\FinancialApiBundle\Transactions
 */
class TransactionsLTABV3Test extends BaseApiTest {

    use MongoDBTrait;

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

}