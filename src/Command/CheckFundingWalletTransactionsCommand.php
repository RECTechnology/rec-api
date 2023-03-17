<?php
namespace App\Command;

use App\DependencyInjection\Commons\Web3ApiManager;
use App\Entity\ConfigurationSetting;
use App\Entity\FundingNFTWalletTransaction;
use App\Entity\NFTTransaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckFundingWalletTransactionsCommand extends SynchronizedContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:funding:check')
            ->setDescription('Check funding transactions')
        ;
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output){
        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $output->writeln('Checking configuration for web3');
        /** @var ConfigurationSetting $configuration */
        $configuration = $em->getRepository(ConfigurationSetting::class)->findOneBy(array('scope' => ConfigurationSetting::NFT_SCOPE, 'name' => 'create_nft_wallet'));
        if($configuration && $configuration->getValue() === 'enabled'){
            $output->writeln('web3 is enabled');
            //get all funding nft wallet transactions in pending
            $txs = $em->getRepository(FundingNFTWalletTransaction::class)->findBy(array(
                'status' => FundingNFTWalletTransaction::STATUS_PENDING
            ));

            /** @var Web3ApiManager $web3Manager */
            $web3Manager = $this->container->get('net.app.commons.web3.api_manager');
            $contract = $this->container->getParameter("atarca_sharable_nft_contract_address");

            /** @var FundingNFTWalletTransaction $tx */
            foreach ($txs as $tx){
                $response = null;
                try{
                    //check transaction
                    $response = $web3Manager->get_transaction_status($contract, $tx->getTxId(), 'transfer');

                }catch (\Exception $e){
                    $tx->setStatus(FundingNFTWalletTransaction::STATUS_FAILED);
                    $em->flush();
                }

                if($tx->getStatus() !== FundingNFTWalletTransaction::STATUS_FAILED){
                    if($response['error'] === '' && $response['status'] === 1){
                        $tx->setStatus(FundingNFTWalletTransaction::STATUS_SUCCESS);
                        $em->flush();
                        //find all NFT transactions from this account and set in created again
                        $nftTxs = $em->getRepository(NFTTransaction::class)->findBy(array(
                            'status' => NFTTransaction::STATUS_FUNDING_PENDING,
                            'from' => $tx->getAccount()
                        ));

                        foreach ($nftTxs as $nftTx){
                            $nftTx->setStatus(NFTTransaction::STATUS_CREATED);
                            $em->flush();
                        }
                    }

                }

            }

            $output->writeln('Crypto transactions finished');
        }else{
            $output->writeln('web3 is disabled, if you want to use it go to settings and enable create_nft_wallet option');
        }

    }

}