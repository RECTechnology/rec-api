<?php
namespace App\Command;

use App\DependencyInjection\Commons\Web3ApiManager;
use App\Entity\ConfigurationSetting;
use App\Entity\FundingNFTWalletTransaction;
use App\Entity\Group;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FundingWalletTransactionsCommand extends SynchronizedContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:funding:execute')
            ->setDescription('Execute funding transactions')
        ;
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output){
        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine.orm.entity_manager');
        $output->writeln('Checking configuration for web3');
        /** @var ConfigurationSetting $configuration */
        $configuration = $em->getRepository(ConfigurationSetting::class)->findOneBy(array('scope' => ConfigurationSetting::NFT_SCOPE, 'name' => 'create_nft_wallet'));
        if($configuration && $configuration->getValue() === 'enabled') {
            $output->writeln('web3 is enabled');

            //get all funding nft wallet transactions in created
            $txs = $em->getRepository(FundingNFTWalletTransaction::class)->findBy(array(
                'status' => FundingNFTWalletTransaction::STATUS_CREATED
            ));
            /** @var Web3ApiManager $web3Manager */
            $web3Manager = $this->container->get('net.app.commons.web3.api_manager');
            $admin_id = $this->container->getParameter("id_group_root");
            /** @var Group $rootAccount */
            $rootAccount = $em->getRepository(Group::class)->find($admin_id);
            $contract_address = $this->container->getParameter("atarca_sharable_nft_contract_address");

            $nonce = $web3Manager->getNonce($contract_address, $rootAccount->getNftWallet())['nonce'];

            $output->writeln("Funding transactions found ".count($txs));
            $output->writeln("Nonce ".$nonce);

            foreach ($txs as $tx){
                $response = null;
                //set status in processing
                $tx->setStatus(FundingNFTWalletTransaction::STATUS_PROCESSING);
                $em->flush();

                try{
                    $output->writeln("Funding transaction account ".$tx->getAccount()->getId());
                    //execute transaction against blockchain
                    $response = $web3Manager->transfer($contract_address, $tx->getAmount(), $tx->getAccount()->getNftWallet(), $rootAccount->getNftWallet(), $rootAccount->getNftWalletPk(), $nonce);
                }catch (\Exception $e){
                    //set transaction status failed
                    $output->writeln("Funding transaction FAILED ");
                    $tx->setStatus(FundingNFTWalletTransaction::STATUS_FAILED);
                    $em->flush();
                }

                if($tx->getStatus() !== FundingNFTWalletTransaction::STATUS_FAILED){
                    $output->writeln("Funding transaction NOT FAILED ");
                    if($response){
                        $output->writeln("Funding transaction we have response");
                        $output->writeln("Funding transaction ".$response['error']);
                        $output->writeln("Funding transaction we have response".$response['message']);
                        if($response['error'] === ''){
                            $output->writeln("Funding transaction SUCCESS ");
                            $nonce++;
                            $tx->setTxId($response['message']);
                            $tx->setStatus(FundingNFTWalletTransaction::STATUS_PENDING);
                            $em->flush();
                        }else{
                            $output->writeln("Funding transaction FAILED on web3Api ");
                            $tx->setStatus(FundingNFTWalletTransaction::STATUS_FAILED);
                            $em->flush();
                        }
                    }

                }

            }

            $output->writeln('Funding transactions finished');

        }else{
            $output->writeln('web3 is disabled, if you want to use it go to settings and enable create_nft_wallet option');
        }

    }

}