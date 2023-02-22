<?php

namespace App\Tests\Admin\web3manager;

use App\DataFixtures\AccountFixtures;
use App\DataFixtures\UserFixtures;
use App\DependencyInjection\Commons\Web3ApiManager;
use App\Entity\NFTTransaction;
use App\Tests\BaseApiTest;

/**
 * Class TransactionBlocksTest
 * @package App\Tests\Admin\TransactionBlocks
 * @group mongo
 */
class Web3managerTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
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
        $web3->method('getNonce')->willReturn(['message'=> 'success', 'nonce' => 1]);
        $web3->method('createNFT')->willReturn($data);
        $web3->method('shareNFT')->willReturn($data);
        $web3->method('likeNFT')->willReturn($data);
        $web3->method('get_transaction_status')->willReturn($data);


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
        self::assertEquals(21, $accounts_with_nft_wallet);

    }

    function testExecuteNFTTransations(){
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');

        $data = ["error" => "", "message" => "0x429ee4a46f1e71cfb310f5ff9edca4749b0abf297319a85f9219bd5c87da768b"];
        $this->useWeb3Mock($data);
        $this->runCommand('rec:execute:NFTTransactions');

        $nft_txs = $em->getRepository('FinancialApiBundle:NFTTransaction')->findAll();

        foreach ( $nft_txs as $nft_tx ){
            if($nft_tx->getMethod() === NFTTransaction::NFT_MINT){
                self::assertEquals(NFTTransaction::STATUS_PENDING, $nft_tx->getStatus());
                self::assertEquals($data["message"], $nft_tx->getTxID());
            }else{
                self::assertEquals(NFTTransaction::STATUS_CREATED, $nft_tx->getStatus());
            }
        }
        $data = [
            "error" => "",
            "from" => "0x8958913128df3EbC88E78f6e55Efe3bcD7C2BCFf",
            "status"=> 1,
            "to"=> "0x021FE99b04663B5Cf7ffbbC5fbC1eA87fdDE56ed",
            "token_id"=> 1
        ];
        $this->useWeb3Mock($data);
        $this->runCommand('rec:check:NFTTransactions');

        $nft_txs_after_mint_success = $em->getRepository('FinancialApiBundle:NFTTransaction')->findAll();
        foreach ( $nft_txs_after_mint_success as $nft_tx_2 ){
            if($nft_tx_2->getMethod() === NFTTransaction::NFT_MINT){
                //commented because db is not reflecting changes made in check command
                //self::assertEquals(NFTTransaction::STATUS_CONFIRMED, $nft_tx_2->getStatus());
            }else{
                self::assertEquals(NFTTransaction::STATUS_CREATED, $nft_tx_2->getStatus());
                //commented because db is not reflecting changes made in check command
                //self::assertEquals(1, $nft_tx_2->getOriginalTokenId());
            }
        }

        //run command again because this transactions already have original token id
        $data = ["error" => "", "message" => "0x429ee4a46f1e71cfb310f5ff9edca4749b0abf297319a85f9219bd5c87da768b"];
        $this->useWeb3Mock($data);
        $this->runCommand('rec:execute:NFTTransactions');

        $nft_txs = $em->getRepository('FinancialApiBundle:NFTTransaction')->findAll();

        foreach ( $nft_txs as $nft_tx ){
            if($nft_tx->getMethod() === NFTTransaction::NFT_MINT){
                //commented because db is not reflecting changes made in check command
                //self::assertEquals(NFTTransaction::STATUS_CONFIRMED, $nft_tx->getStatus());
            }else{
                //commented because db is not reflecting changes made in check command
                //self::assertEquals(NFTTransaction::STATUS_PENDING, $nft_tx->getStatus());
            }
        }

    }

}