<?php
namespace App\FinancialApiBundle\Command;

use App\FinancialApiBundle\DependencyInjection\App\Commons\TxBlockValidator;
use App\FinancialApiBundle\Entity\DelegatedChangeData;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\TransactionBlockLog;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\FinancialApiBundle\Entity\DelegatedChange;

class TransactionBlocksValidatorCommand extends SynchronizedContainerAwareCommand{
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
            ->setName('rec:transaction_block:validate')
            ->setDescription('Validate pending transaction blocks')
        ;
    }

    private function log(OutputInterface $output, $message, $severity = DelegatedChangeV2Command::SEVERITY_DEBUG){
        $output->writeln(implode(" - ", [(new DateTime())->format('Y-m-d H:i:s Z'), $severity, $message]));
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output){
        # Summary steps:
        # for each exchange with status = scheduled:
        #       for each exchange_data:
        #           if card_data_present:
        #               execute_python_bot;
        #           else:
        #               execute_lemonway_charge;

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $dcRepo = $em->getRepository(DelegatedChange::class);

        $txBlocks = $dcRepo->findBy(['status' => DelegatedChange::STATUS_PENDING_VALIDATION]);

        $this->log($output, "Found " . count($txBlocks) . " transaction blocks to process");

        //get service tx block validator
        /** @var TxBlockValidator $txBlockValidator */
        $txBlockValidator = $this->getContainer()->get('net.app.commons.tx_block_validator');

        /** @var DelegatedChange $txBlock */
        foreach ($txBlocks as $txBlock){
            $this->log($output, "Processing transaction block: " . $txBlock->getId());
            $txBlock->setStatus(DelegatedChange::STATUS_VALIDATING);
            $em->flush();

            //validate tx block
            $validation = $txBlockValidator->validateTxBlock($txBlock);

            $logs_repo = $em->getRepository(TransactionBlockLog::class);
            $errors = $logs_repo->findBy(['block_txs' => $txBlock->getId(), 'type' => 'error']);
            $warns = $logs_repo->findBy(['block_txs' => $txBlock->getId(), 'type' => 'warn']);

            if(count($errors)> 0){
                //mark as invalid
                $txBlock->setStatus(DelegatedChange::STATUS_INVALID);
                $txBlock->setWarnings(count($warns));
                $em->flush();
            }else{
                $txBlock->setWarnings(count($warns));
                //generate tx block data
                $data = $validation['data'];
                //start tx
                $em->getConnection()->beginTransaction();
                try{
                    // do stuff
                    foreach ($data as $txData){
                        $account = $em->getRepository(Group::class)->find($txData[0]);
                        $exchanger = $em->getRepository(Group::class)->find($txData[1]);
                        $amount = $txData[2];
                        $sender = $em->getRepository(Group::class)->find($txData[3]);
                        $row = $txData[4];

                        //generate txBlockData
                        $txBlockData = new DelegatedChangeData();
                        $txBlockData->setStatus(DelegatedChangeData::STATUS_CREATED);
                        $txBlockData->setAmount($amount);
                        $txBlockData->setAccount($account);
                        $txBlockData->setExchanger($exchanger);
                        $txBlockData->setSender($sender);
                        $txBlockData->setDelegatedChange($txBlock);

                        $em->persist($txBlockData);
                        $em->flush();
                    }
                    $txBlock->setStatus(DelegatedChange::STATUS_DRAFT);
                    $em->getConnection()->commit();
                    $em->flush();
                } catch (\Exception $e) {
                    $em->getConnection()->rollBack();
                    $txBlock->setStatus(DelegatedChange::STATUS_FAILED);
                    $em->flush();

                }

            }

            $this->log($output, "Done transaction block: " . $txBlock->getId());
        }
        $this->log($output, "Finish");
    }

}
