<?php

namespace Test\FinancialApiBundle\Admin\Web3managerTest;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\DependencyInjection\App\Commons\Web3ApiManager;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class TransactionBlocksTest
 * @package Test\FinancialApiBundle\Admin\TransactionBlocks
 * @group mongo
 */
class Web3managerTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testCreateWallet(){
        $data = ["message" => "Success", "wallet" => ["address"=> "0xafA513ce24B247635Faebc63FB4a9cA84C89Abda",
            "private_key"=> "0xe82079bc613d36b6532e3021a8158488655970808a5f5ffb08e2fe9bbd7a8f3c"]];
        $this->useWeb3Mock($data);
        $route = '/admin/v4/nft_wallet/2';
        $resp = $this->requestJson('POST', $route);
        $content = json_decode($resp->getContent(), true);
        self::assertEquals($data, $content["data"]);

    }

    /**
     * @param array $data
     */
    private function useWeb3Mock(array $data): void
    {
        $web3 = $this->createMock(Web3ApiManager::class);
        $web3->method('createWallet')->willReturn($data);
        $web3->method('createNFT')->willReturn($data);


        $this->inject('net.app.commons.web3.api_manager', $web3);
    }

    function testCreateWalletsForAllAccounts(){
        $data = ["message" => "Success", "wallet" => ["address"=> "0xafA513ce24B247635Faebc63FB4a9cA84C89Abda",
            "private_key"=> "0xe82079bc613d36b6532e3021a8158488655970808a5f5ffb08e2fe9bbd7a8f3c"]];
        $this->useWeb3Mock($data);

        $this->runCommand('rec:create:NFTwallet');

        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        $accounts = $em->getRepository('FinancialApiBundle:Group')->findAll();

        $accounts_with_nft_wallet = 0;
        foreach ( $accounts as $account ){
            if($account->getNftWallet() !== ''){
                $accounts_with_nft_wallet++;
            }
        }
        self::assertEquals(5, $accounts_with_nft_wallet);

    }

}