<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/15/14
 * Time: 1:27 PM
 */

namespace App\FinancialApiBundle\Command;


use App\FinancialApiBundle\DependencyInjection\App\Commons\Web3ApiManager;
use App\FinancialApiBundle\Entity\NFTTransaction;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckNFTTransactionsCommand extends SynchronizedContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:check:NFTTransactions')
            ->setDescription('Check NFT Transactions')
        ;
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output){
        $em = $this->getContainer()->get('doctrine')->getManager();

        $nft_transactions = $em->getRepository(NFTTransaction::class)->findBy(
            ['status' => NFTTransaction::STATUS_PENDING]);

        if(count($nft_transactions) > 0){
            $output->writeln("Found ".count($nft_transactions)." transactions to check");
            /** @var Web3ApiManager $web3Manager */
            $web3Manager = $this->getContainer()->get('net.app.commons.web3.api_manager');
            /** @var NFTTransaction $nft_transaction */
            foreach ( $nft_transactions as $nft_transaction ) {
                $response = null;
                try{
                    //check NFTTransaction against blockchain
                    $contract = $this->getContract($nft_transaction);
                    if($contract){
                        $response = $web3Manager->get_transaction_status($contract, $nft_transaction->getTxId(), 'nft');
                    }else{
                        $response = null;
                        $output->writeln( 'Contract not found');
                    }

                }catch (Exception $e) {
                    $output->writeln( 'Error during call: '. $e->getMessage());
                }

                //if confirmed change status and save token id, if is mint save in original token id, if not in shared token id
                if($response){

                    if($response['error'] === '' && $response['status'] === 1){
                        //transaction is confirmed
                        $nft_transaction->setStatus(NFTTransaction::STATUS_CONFIRMED);
                        if($nft_transaction->getMethod() === NFTTransaction::NFT_MINT){
                            $nft_transaction->setOriginalTokenId($response['token_id']);
                            if($nft_transaction->getContractName() === NFTTransaction::B2C_SHARABLE_CONTRACT){
                                //update token reward with the token id
                                $tokenReward = $nft_transaction->getTokenReward();
                                $tokenReward->setTokenid($response['token_id']);
                            }
                        }else{
                            $nft_transaction->setSharedTokenId($response['token_id']);
                        }
                        $em->flush();

                        //if is mint find all tx in created that doesnt have original token id and method is like or share
                        //and the topic id is the same than this one and add the original token id to this transactions
                        if($nft_transaction->getMethod() === NFTTransaction::NFT_MINT){
                            $relatedTransactions = $em->getRepository(NFTTransaction::class)->findBy(array(
                                'status' => NFTTransaction::STATUS_CREATED,
                                'topic_id' => $nft_transaction->getTopicId(),
                                'original_token_id' => null
                            ));
                            /** @var NFTTransaction $relatedTransaction */
                            foreach ($relatedTransactions as $relatedTransaction){
                                $relatedTransaction->setOriginalTokenId($response['token_id']);
                            }
                            $em->flush();
                        }
                    }
                }
            }
        }
    }

    public function getContract(NFTTransaction $tx){
        if($tx->getContractName() === NFTTransaction::B2C_SHARABLE_CONTRACT){
            return $this->getContainer()->getParameter("atarca_b2c_sharable_nft_contract_address");
        }

        switch ($tx->getMethod()){
            case NFTTransaction::NFT_SHARE:
            case NFTTransaction::NFT_MINT:
            case NFTTransaction::NFT_BURN:
                return $this->getContainer()->getParameter("atarca_sharable_nft_contract_address");
            case NFTTransaction::NFT_LIKE:
            case NFTTransaction::NFT_UNLIKE:
                return $this->getContainer()->getParameter("atarca_like_nft_contract_address");
            default:
                return null;
        }
    }
}