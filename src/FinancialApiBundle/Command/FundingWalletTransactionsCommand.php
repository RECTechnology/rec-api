<?php
namespace App\FinancialApiBundle\Command;

use App\FinancialApiBundle\DependencyInjection\App\Commons\Web3ApiManager;
use App\FinancialApiBundle\Entity\FundingNFTWalletTransaction;
use App\FinancialApiBundle\Entity\Group;
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
        $em = $this->getContainer()->get('doctrine')->getManager();
        //get all funding nft wallet transactions in created
        $txs = $em->getRepository(FundingNFTWalletTransaction::class)->findBy(array(
            'status' => FundingNFTWalletTransaction::STATUS_CREATED
        ));
        /** @var Web3ApiManager $web3Manager */
        $web3Manager = $this->getContainer()->get('net.app.commons.web3.api_manager');
        $admin_id = $this->getContainer()->getParameter("id_group_root");
        /** @var Group $rootAccount */
        $rootAccount = $em->getRepository(Group::class)->find($admin_id);
        $contract_address = $this->getContainer()->getParameter("atarca_sharable_nft_contract_address");

        $nonce = $web3Manager->getNonce($rootAccount->getNftWallet(), $rootAccount)['nonce'];

        $output->writeln("Funding transactions found ".count($txs));

        foreach ($txs as $tx){
            $response = null;
            //set status in processing
            $tx->setStatus(FundingNFTWalletTransaction::STATUS_PROCESSING);
            $em->flush();

            try{
                $output->writeln("Funding transaction account ".$tx->getAccount()->getId());
                //execute transaction against blockchain
                $response = $web3Manager->transfer($contract_address, $tx->getAmount(), $tx->getAccount(), $rootAccount->getNftWallet(), $rootAccount->getNftWalletPk(), $nonce);
            }catch (\Exception $e){
                //set transaction status failed
                $output->writeln("Funding transaction FAILED ");
                $tx->setStatus(FundingNFTWalletTransaction::STATUS_FAILED);
                $em->flush();
            }

            if($tx->getStatus() !== FundingNFTWalletTransaction::STATUS_FAILED){
                if($response){
                    if($response['error'] === ''){
                        $output->writeln("Funding transaction SUCCESS ");
                        $nonce++;
                        $tx->setTxId($response['message']);
                        $tx->setStatus(FundingNFTWalletTransaction::STATUS_PENDING);
                        $em->flush();
                    }
                }

            }

        }

        $output->writeln('Funding transactions finished');
    }

}