<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/15/14
 * Time: 1:27 PM
 */

namespace App\Command;


use App\DependencyInjection\Commons\MailerAwareTrait;
use App\DependencyInjection\Commons\Web3ApiManager;
use App\Entity\ConfigurationSetting;
use App\Entity\FundingNFTWalletTransaction;
use App\Entity\Group;
use App\Entity\NFTTransaction;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteNFTTransactionsCommand extends SynchronizedContainerAwareCommand
{

    use MailerAwareTrait;

    protected function configure()
    {
        $this
            ->setName('rec:execute:NFTTransactions')
            ->setDescription('Create NFT Transactions')
        ;
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output){
        $em = $this->container->get('doctrine.orm.entity_manager');

        $nft_transactions = $em->getRepository(NFTTransaction::class)->findBy(
            ['status' => NFTTransaction::STATUS_CREATED]);

        if(count($nft_transactions) > 0){
            $output->writeln("Found ".count($nft_transactions)." transactions to process");
            /** @var Web3ApiManager $web3Manager */
            $web3Manager = $this->container->get('net.app.commons.web3.api_manager');

            $nonces = [];
            //$nonce = $web3Manager->get_nonce();
            /** @var NFTTransaction $nft_transaction */
            foreach ( $nft_transactions as $nft_transaction ) {
                $response = null;
                try{
                    /** @var Group $sender */
                    $sender = $nft_transaction->getFrom();
                    /** @var Group $receiver */
                    $receiver = $nft_transaction->getTo();
                    $sender_id = $sender->getId();
                    $receiver_id = $receiver->getId();

                    $contract_address = $this->getContractAddress($nft_transaction);

                    $output->writeln($nft_transaction->getMethod()."ing transaction, from ".$sender_id." to ".$receiver_id." contract => ".$contract_address);
                    if (!array_key_exists($sender_id, $nonces)) {
                        $nonces[$sender_id] = $web3Manager->getNonce($contract_address, $sender->getNftWallet())['nonce'];
                    }
                    $output->writeln("Nonce for wallet ".$sender->getNftWallet()." -> ".$nonces[$sender_id]);
                    if($nft_transaction->getMethod() === NFTTransaction::NFT_MINT){
                        $output->writeln("Mint NFT transaction to ".$receiver_id);
                        $response = $web3Manager->createNFT(
                            $contract_address,
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
                            $contract_address,
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
                            $contract_address,
                            $nft_transaction->getOriginalTokenId(),
                            $sender->getNftWallet(),
                            $sender->getNftWalletPk(),
                            $nonces[$sender_id]
                        );
                    }

                    if($nft_transaction->getMethod() === NFTTransaction::NFT_BURN){
                        $output->writeln('Burn NFT token id: '.$nft_transaction->getSharedTokenId());
                        $response = $web3Manager->burnNFT(
                            $contract_address,
                            $nft_transaction->getSharedTokenId(),
                            $sender->getNftWallet(),
                            $sender->getNftWalletPk(),
                            $nonces[$sender_id]
                        );
                    }

                    if($nft_transaction->getMethod() === NFTTransaction::NFT_UNLIKE){
                        $output->writeln('Burn NFT like token id: '.$nft_transaction->getSharedTokenId());
                        $response = $web3Manager->burnNFT(
                            $contract_address,
                            $nft_transaction->getSharedTokenId(),
                            $sender->getNftWallet(),
                            $sender->getNftWalletPk(),
                            $nonces[$sender_id]
                        );
                    }



                }catch (Exception $e) {
                    $output->writeln( 'Error during call: '.strval($e->getMessage()));
                }

                if($response){
                    $output->writeln("Response error-> ". $response['error']." message -> ".$response['message']);
                    //check if it was successfull tx
                    if($response['error'] == '' and strlen($response['message']) == 66){
                        ++$nonces[$sender_id];
                        $nft_transaction->setTxId($response['message']);
                        $nft_transaction->setStatus(NFTTransaction::STATUS_PENDING);
                        $nft_transaction->setErrorLog(null);
                        $em->persist($nft_transaction);
                        $em->flush();
                        $output->writeln("NFT transaction from " .$sender_id. " to ".$receiver_id." success");
                    }else{
                        $code = $response['message']['error'];
                        if($code === -32000){
                            if($nft_transaction->getMethod() === NFTTransaction::NFT_MINT){
                                //send email to admin because mint is from admin and admin needs funding
                                $this->sendEmail("Account needs MATIC funding", "Account ".$sender_id." needs funding to mint tokens. please send some matic to: ".$sender->getNftWallet());
                            }else{
                                //get amount from Configuration settings table
                                $setting = $em->getRepository(ConfigurationSetting::class)->findOneBy(array(
                                    'scope' => 'nft_wallet',
                                    'name' => 'default_funding_amount'
                                ));
                                if($setting){
                                    $fundingAmount = $setting->getValue();
                                }else{
                                    //TODO decidir  que hacer si el parametro no esta definido, por ahoira setteo uno por default
                                    $fundingAmount = 100000000;
                                }
                                //not enough balance
                                //create funding transaction
                                $fundingTx = new FundingNFTWalletTransaction();
                                $fundingTx->setStatus(FundingNFTWalletTransaction::STATUS_CREATED);
                                $fundingTx->setAccount($sender);
                                $fundingTx->setAmount($fundingAmount);

                                $nft_transaction->setStatus(NFTTransaction::STATUS_FUNDING_PENDING);

                                $em->persist($fundingTx);
                                $em->flush();
                            }

                        }else{
                            $nft_transaction->setErrorLog($code);
                            $nft_transaction->setStatus(NFTTransaction::STATUS_FAILED);
                            $em->flush();
                            $output->writeln("NFT transaction from " .$sender_id. " to ".$receiver_id." error, log saved");
                        }
                    }

                }else{
                    $output->writeln("NFT transaction from " .$sender_id. " to ".$receiver_id." failed with no response");
                }

            }
        }
    }

    private function getContractAddress(NFTTransaction $tx){
        if($tx->getContractName() === NFTTransaction::B2C_SHARABLE_CONTRACT){
            return $this->container->getParameter("atarca_b2c_sharable_nft_contract_address");
        }

        switch ($tx->getMethod()){
            case NFTTransaction::NFT_SHARE:
            case NFTTransaction::NFT_MINT:
            case NFTTransaction::NFT_BURN:
                return $this->container->getParameter("atarca_sharable_nft_contract_address");
            case NFTTransaction::NFT_LIKE:
            case NFTTransaction::NFT_UNLIKE:
                return $this->container->getParameter("atarca_like_nft_contract_address");
            default:
                return null;
        }

    }

    private function sendEmail($subject, $body){

        $no_replay = $this->container->getParameter('no_reply_email');
        $emails_list = $this->container->getParameter("resume_admin_emails_list");

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($no_replay)
            ->setTo($emails_list)
            ->setBody(
                $this->container->get('templating')
                    ->render('Email/empty_email.html.twig',
                        [
                            'mail' => [
                                'subject' => $subject,
                                'body' => $body
                            ]
                        ]
                    )
            )
            ->setContentType('text/html');

        $this->mailer->send($message);
    }

}