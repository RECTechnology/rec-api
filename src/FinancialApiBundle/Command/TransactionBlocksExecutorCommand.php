<?php
namespace App\FinancialApiBundle\Command;

use App\FinancialApiBundle\DependencyInjection\App\Commons\TxBlockValidator;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\DelegatedChangeData;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\TransactionBlockLog;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\FinancialApiBundle\Entity\DelegatedChange;

class TransactionBlocksExecutorCommand extends SynchronizedContainerAwareCommand{
    const SEVERITY_DEBUG = "DEBUG";
    const SEVERITY_INFO = "INFO";
    const SEVERITY_WARN = "WARN";
    const SEVERITY_ERROR = "ERROR";
    const SEVERITY_CRITICAL = "CRITICAL";
    const SEVERITY_ALERT = "ALERT";
    const SEVERITY_EMERGENCY = "EMERG";

    protected function configure()
    {
        $this
            ->setName('rec:transaction_block:execute')
            ->setDescription('Execute pending transaction blocks data')
        ;
    }

    private function log(OutputInterface $output, $message, $severity = DelegatedChangeV2Command::SEVERITY_DEBUG){
        $output->writeln(implode(" - ", [(new DateTime())->format('Y-m-d H:i:s Z'), $severity, $message]));
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output){
        # Summary steps:
        # for each exchange with status = scheduled:
        #       for each exchange_data:
        #           execute_internal_tx
        #            update statistics

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $dcRepo = $em->getRepository(DelegatedChange::class);

        $txBlocks = $dcRepo->findBy(['status' => DelegatedChange::STATUS_SCHEDULED]);

        $this->log($output, "Found " . count($txBlocks) . " transaction blocks to process");

        $txFlowHandler = $this->getContainer()->get('net.app.commons.transaction_flow_handler');
        $satoshi_decimals = 1e6; // amount in cents
        $now = new Datetime('NOW');
        /** @var DelegatedChange $txBlock */
        foreach ($txBlocks as $txBlock){
            if($txBlock->getScheduledAt() <= $now){
                $this->log($output, "Processing transaction block: " . $txBlock->getId());
                $txBlock->setStatus(DelegatedChange::STATUS_IN_PROGRESS);
                $txBlock->setResult('failed_tx', 0);
                $em->flush();

                $log_handler = $this->getContainer()->get('net.app.commons.tx_block_log_handler');
                $log_text = sprintf('From %s to %s. Sending the transactions of this block',
                    DelegatedChange::STATUS_SCHEDULED,
                    DelegatedChange::STATUS_IN_PROGRESS);
                $log_handler->persistLog($txBlock, TransactionBlockLog::TYPE_DEBUG, $log_text);

                foreach ($txBlock->getData() as $txData) {
                    if ($txData->getStatus() !== DelegatedChangeData::STATUS_SUCCESS) {
                        try{
                            $this->log($output, "Processing entry: " . $txData->getId());
                            $tx = $txFlowHandler->sendRecsWithIntermediary(
                                $txData->getSender(),
                                $txData->getExchanger(),
                                $txData->getAccount(),
                                $txData->getAmount() * $satoshi_decimals,
                                $txBlock->getName()
                            );
                            $this->updateStatus($txData, $tx, $em, $txBlock, $satoshi_decimals, $output);
                        } catch (\Exception $e) {
                            $this->updateStatus($txData, null, $em, $txBlock, $satoshi_decimals, $output);
                            break;
                        }
                    }
                }
                $this->log($output, "Done transaction block: " . $txBlock->getId());
            }
        }
        $this->log($output, "Finish");
    }

    /**
     * @param DelegatedChangeData $dcd
     * @param mixed $tx
     * @param EntityManagerInterface $em
     * @param DelegatedChange $dc
     * @param float $satoshi_decimals
     */
    protected function updateStatus(DelegatedChangeData $dcd, $tx, EntityManagerInterface $em, DelegatedChange $dc, float $satoshi_decimals, OutputInterface $output): void
    {
        $log_handler = $this->getContainer()->get('net.app.commons.tx_block_log_handler');
        if (isset($tx) && $tx->getStatus() === 'success') {
            $dcd->setStatus(DelegatedChangeData::STATUS_SUCCESS);
            $dc->setResult('success_tx', $dc->getStatistics()["result"]["success_tx"] + 1);
            $dc->setResult('issued_rec', $dc->getStatistics()["result"]["issued_rec"] + $dcd->getAmount() * $satoshi_decimals);
            $output->writeln("TX(id): " . $tx->getId());
            if($dc->getStatistics()["scheduled"]["tx_to_execute"] == $dc->getStatistics()["result"]["success_tx"]){
                $dc->setStatus(DelegatedChange::STATUS_FINISHED);
                $log_text = sprintf('From %s to %s. All the transactions executed successfully',
                    DelegatedChange::STATUS_IN_PROGRESS,
                    DelegatedChange::STATUS_FINISHED);
                $log_handler->persistLog($dc, TransactionBlockLog::TYPE_DEBUG, $log_text);
            }

        }else{
            $dcd->setStatus(DelegatedChangeData::STATUS_ERROR);
            $dc->setResult('failed_tx', $dc->getStatistics()["result"]["failed_tx"] + 1);
            $dc->setStatus(DelegatedChange::STATUS_FAILED);
            $this->log(
                $output,
                "Transaction creation failed",
                DelegatedChangeV2Command::SEVERITY_CRITICAL
            );
            $log_text = sprintf('From %s to %s. An unexpected error occurred while executing the transaction %s 
             error: %s .Please try to resolve the issue manually and click "Retry Send" to retry sending transactions 
             since this last failed tx. If you cannot solve the problem, you must "Mark as incomplete" this block of 
             transactions and create a new one.',
                DelegatedChange::STATUS_IN_PROGRESS,
                DelegatedChange::STATUS_FAILED,
                $dc->getId(),
                "Transaction creation failed"
            );
            $log_handler->persistLog($dc, TransactionBlockLog::TYPE_ERROR, $log_text);
        }
        $dcd->setTransaction($tx);
        $em->persist($dcd);
        $em->persist($dc);
        $em->flush();
    }

}
