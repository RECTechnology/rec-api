<?php

namespace Test\FinancialApiBundle\Admin\LemonWay;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UploadManager;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\LemonDocumentKind;
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

    function testLemonTransfer(){
        $user_id = 1;
        $em = $this->client->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        $user_pin = $em->getRepository(User::class)->findOneBy(['id' => 1])->getPin();
        $private_account_id = $em->getRepository(Group::class)
            ->findOneBy(['type' => Group::ACCOUNT_TYPE_PRIVATE, 'kyc_manager' => $user_id])->getId();
        $company_account_id = $em->getRepository(Group::class)
            ->findOneBy(['type' => Group::ACCOUNT_TYPE_ORGANIZATION, 'kyc_manager' => $user_id])->getId();
        $data = ['status' => Transaction::$STATUS_RECEIVED,
            'company_id' => $private_account_id,
            'amount' => 6000,
            'commerce_id' => $company_account_id,
            'concept' => 'test recharge',
            'pin' => $user_pin,
            'save_card' => 0];

        $lw = $this->createMock(LemonWayMethod::class);
        $lw->method('getCurrency')->willReturn("EUR");
        $lw->method('getPayInInfoWithCommerce')->willReturn($data);
        $lw->method('getCname')->willReturn('lemonway');
        $lw->method('getType')->willReturn('in');

        $this->override('net.app.in.lemonway.v1', $lw);


        $rest_group1 = $this->requestJson('GET', '/admin/v3/group/'.$private_account_id);
        $before = json_decode($rest_group1->getContent(), true);

        $route = '/methods/v1/in/lemonway';
        $resp = $this->requestJson('POST', $route, $data);
        $content_post = json_decode($resp->getContent(), true);


        $this->runCommand('rec:fiat:check');
        $this->runCommand('rec:crypto:check');
        $this->runCommand('rec:crypto:check');


        $rest_group2 = $this->requestJson('GET', '/admin/v3/group/'.$private_account_id);
        $after = json_decode($rest_group2->getContent(), true);
        self::assertEquals($before["data"]["wallets"][0]["balance"] + $data['amount'] * 1e6,
            $after["data"]["wallets"][0]["balance"]);

        $rest_account = $this->requestJson('GET', '/admin/v3/accounts?campaigns=1');
        self::assertEquals(200, $rest_account->getStatusCode());

        $account_content = json_decode($rest_account->getContent(), true);
        self::assertEquals(Campaign::BONISSIM_CAMPAIGN_NAME, $account_content["data"]["elements"][0]['name']);


    }
}
