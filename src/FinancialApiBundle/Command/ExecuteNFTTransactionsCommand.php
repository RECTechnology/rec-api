<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/15/14
 * Time: 1:27 PM
 */

namespace App\FinancialApiBundle\Command;


use App\FinancialApiBundle\DependencyInjection\App\Commons\Web3ApiManager;
use App\FinancialApiBundle\Entity\FundingNFTWalletTransaction;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\NFTTransaction;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteNFTTransactionsCommand extends SynchronizedContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:execute:NFTTransactions')
            ->setDescription('Create NFT Transactions')
        ;
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output){
        $em = $this->getContainer()->get('doctrine')->getManager();

        $nft_transactions = $em->getRepository(NFTTransaction::class)->findBy(
            ['status' => NFTTransaction::STATUS_CREATED]);

        if(count($nft_transactions) > 0){
            $output->writeln("Found ".count($nft_transactions)." transactions to process");
            /** @var Web3ApiManager $web3Manager */
            $web3Manager = $this->getContainer()->get('net.app.commons.web3.api_manager');

            $sharable_address = $this->getContainer()->getParameter("atarca_sharable_nft_contract_address");
            $like_address = $this->getContainer()->getParameter("atarca_like_nft_contract_address");
            $endorse_address = $this->getContainer()->getParameter("atarca_endorse_nft_contract_address");
            $nonces = [];
            //$nonce = $web3Manager->get_nonce();
            foreach ( $nft_transactions as $nft_transaction ) {
                $response = null;
                try{
                    /** @var Group $sender */
                    $sender = $nft_transaction->getFrom();
                    /** @var Group $receiver */
                    $receiver = $nft_transaction->getTo();
                    $sender_id = $sender->getId();
                    $receiver_id = $receiver->getId();

                    $output->writeln($nft_transaction->getMethod()."ing transaction, from ".$sender_id." to ".$receiver_id);
                    if (!array_key_exists($sender_id, $nonces)) {
                        $nonces[$sender_id] = $web3Manager->getNonce($sharable_address, $sender->getNftWallet())['nonce'];
                    }
                    $output->writeln("Nonce for wallet ".$sender->getNftWallet()." -> ".$nonces[$sender_id]);
                    if($nft_transaction->getMethod() === NFTTransaction::NFT_MINT){
                        $output->writeln("Mint NFT transaction to ".$receiver_id);
                        $response = $web3Manager->createNFT(
                            $sharable_address,
                            $receiver->getNftWallet(),
                            $sender->getNftWallet(),
                            $sender->getNftWalletPk(),
                            $nonces[$sender_id]
                        );
                    }

                    if($nft_transaction->getMethod() === NFTTransaction::NFT_SHARE and
                        $nft_transaction->getOriginalTokenId() != null){
                        $output->writeln("Share NFT transaction from" . $sender_id. " to ". $receiver_id);
                        $response = $web3Manager->shareNFT(
                            $sharable_address,
                            $receiver->getNftWallet(),
                            $nft_transaction->getOriginalTokenId(),
                            $sender->getNftWallet(),
                            $sender->getNftWalletPk(),
                            $nonces[$sender_id]
                        );
                    }

                    if($nft_transaction->getMethod() === NFTTransaction::NFT_LIKE and
                        $nft_transaction->getOriginalTokenId() != null){
                        $output->writeln("Like NFT transaction from" . $sender_id. " to ". $receiver_id);
                        $response = $web3Manager->likeNFT(
                            $like_address,
                            $nft_transaction->getOriginalTokenId(),
                            $sender->getNftWallet(),
                            $sender->getNftWalletPk(),
                            $nonces[$sender_id]
                        );
                    }



                }catch (Exception $e) {
                    $output->writeln( 'Error during call: '.strval($e->getMessage()));
                }

                if($response){
                    $output->writeln("Response". $response);
                    //check if it was successfull tx
                    if($response['error'] == '' and strlen($response['message']) == 66){
                        $nonces[$sender_id] += 1;
                        $nft_transaction->setTxId($response['message']);
                        $nft_transaction->setStatus(NFTTransaction::STATUS_PENDING);
                        $em->persist($nft_transaction);
                        $em->flush();
                        $output->writeln("NFT transaction from " .$sender_id. " to ".$receiver_id." success");
                    }else{
                        $code = $response['error'];
                        if($code === -32000){
                            if($nft_transaction->getMethod() === NFTTransaction::NFT_MINT){
                                //TODO send email to admin because mint is from admin and admin needs funding
                            }else{
                                //not enough balance
                                //create funding transaction
                                $fundingTx = new FundingNFTWalletTransaction();
                                $fundingTx->setStatus(FundingNFTWalletTransaction::STATUS_CREATED);
                                $fundingTx->setAccount($sender);
                                $fundingTx->setAmount(10000);

                                $nft_transaction->setStatus(NFTTransaction::STATUS_FUNDING_PENDING);

                                $em->persist($fundingTx);
                                $em->flush();
                            }

                        }
                    }

                }else{
                    $output->writeln("NFT transaction from " .$sender_id. " to ".$receiver_id." failed");
                }

            }
}
    }
}