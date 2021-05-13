<?php

namespace Test\FinancialApiBundle\Admin\LemonWay;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UploadManager;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\LemonDocumentKind;
use App\FinancialApiBundle\Entity\PaymentOrder;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserWallet;
use App\FinancialApiBundle\Exception\AppException;
use App\FinancialApiBundle\Financial\Driver\LemonWayInterface;
use App\FinancialApiBundle\Financial\Methods\LemonWayMethod;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Test\FinancialApiBundle\Admin\AdminApiTest;
use Test\FinancialApiBundle\Utils\MongoDBTrait;

/**
 * Class RechargeRecsTest
 * @package Test\FinancialApiBundle\Admin\LemonWay
 */
class RechargeRecsTest extends AdminApiTest {

    use MongoDBTrait;

    function setUp(): void
    {
        parent::setUp();
        $this->setClientIp($this->faker->ipv4);

    }

    function _testLemonTransfer(){
        $user_id = 1;
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        $user_pin = $em->getRepository(User::class)->findOneBy(['id' => $user_id])->getPin();

        $route = "/admin/v3/user/{$user_id}";
        $resp = $this->rest('PUT', $route, ['private_tos_campaign' => true]);
        self::assertTrue($resp->private_tos_campaign);

        $private_bonissim_account = $this->rest('GET', "/user/v3/accounts?campaigns=1&type=PRIVATE&kyc_manager=".$user_id);
        self::assertCount(0, $private_bonissim_account);
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

        $this->override('net.app.in.lemonway.v1', $lw);
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
}
