<?php
namespace Telepay\FinancialApiBundle\Command;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Process\Process;
use Telepay\FinancialApiBundle\Controller\Transactions\IncomingController2;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\DelegatedChange;
use Telepay\FinancialApiBundle\Entity\DelegatedChangeData;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\User;
use Telepay\FinancialApiBundle\Financial\Methods\LemonWayMethod;

class DelegatedChangeV2Command extends SynchronizedContainerAwareCommand{
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
            ->setName('rec:delegated_change:run')
            ->setDescription('Runs the delegated exchange (V2) operations')
        ;
    }

    private function createLemonwayTx($amount, Group $account, Group $exchanger){

        $params = [
            'concept' => 'Internal exchange',
            'amount' => $amount,
            'commerce_id' => $exchanger->getId()
        ];

        /** @var IncomingController2 $txm */
        $txm = $this->getContainer()->get('app.incoming_controller');

        return $txm->createTransaction(
            $params,
            1,
            'in',
            'lemonway',
            $account->getKycManager()->getId(),
            $account,
            '127.0.0.2' # return Response, NOTE: if IP is '127.0.0.1', the return type is String, else Response
        );

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

        /** @var DocumentManager $odm */
        $odm = $this->getContainer()->get('doctrine_mongodb.odm.document_manager');

        $dcRepo = $em->getRepository('TelepayFinancialApiBundle:DelegatedChange');

        $txRepo = $odm->getRepository("TelepayFinancialApiBundle:Transaction");

        $changes = $dcRepo->findBy(['status' => DelegatedChange::STATUS_SCHEDULED]);

        $this->log($output, "Found " . count($changes) . " delegated changes to process");
        /** @var DelegatedChange $dc */
        foreach ($changes as $dc){
            $this->log($output, "Processing delegated change: " . $dc->getId() . " with " . count($dc->getData()) . " entries");
            $dc->setStatus(DelegatedChange::STATUS_IN_PROGRESS);
            $em->persist($dc); $em->flush();
            /** @var DelegatedChangeData $dcd */
            foreach ($dc->getData() as $dcd) {
                $this->log($output, "Processing entry: " . $dcd->getId());
                # Card is not saved
                if(!$dcd->getAccount()->getKycManager()->hasSavedCards()){
                    $this->log($output, "Card is NOT saved, launching lw bot");
                    $this->log($output,"script: " . $this->get('kernel')->getRootDir() . "/../docker/prod/cron/pay-cli.py");
                    /** @var IncomingController2 $txm */
                    $txm = $this->getContainer()->get('app.incoming_controller');
                    $resp = $txm->remoteDelegatedTransactionPlain(
                        [
                            "dni" => $dcd->getAccount()->getKycManager()->getDni(),
                            "cif" => $dcd->getExchanger()->getCIF(),
                            "amount" => $dcd->getAmount()
                        ]
                    );

                    $this->log($output, "RESP: " . print_r($resp, true));

                    # if received is ok
                    if (strpos($resp, 'created') !== false) {

                        if(preg_match("/ID: ([a-zA-Z0-9]+)/", $resp, $matches)) {
                            $txId = $matches[1];

                            /** @var Transaction $tx */
                            $tx = $txRepo->find($txId);
                            $expDate = explode("/", $dcd->getExpiryDate());

                            $this->log($output, "launching bot with params: " . implode(" ",
                                    [
                                        $tx->getPayInInfo()["payment_url"],
                                        $dcd->getAccount()->getKycManager()->getName(),
                                        $dcd->getPan(),
                                        $expDate[0],
                                        $expDate[1],
                                        $dcd->getCvv2()
                                    ]
                                )
                            );

                            $botResult = $this->launchBot(
                                $tx->getPayInInfo()["payment_url"],
                                $dcd->getAccount()->getKycManager()->getName(),
                                $dcd->getPan(),
                                $expDate[0],
                                $expDate[1],
                                $dcd->getCvv2()
                            );
                            if($botResult) {
                                $output->writeln("Bot payment success");
                                $dcd->setStatus(DelegatedChangeData::STATUS_SUCCESS);
                                $em->persist($dcd); $em->flush();
                            }
                            else {
                                $output->writeln("Bot payment error");
                                $dcd->setStatus(DelegatedChangeData::STATUS_ERROR);
                                $em->persist($dcd); $em->flush();
                            }
                        }
                        else {
                            $this->log($output, "Failed to fetch txid");
                            $dcd->setStatus(DelegatedChangeData::STATUS_ERROR);
                            $em->persist($dcd); $em->flush();
                        }
                    }
                }
                # Card is saved, launch lemonway
                else {
                    $this->log($output, "Card is saved, creating lw API tx");
                    try {
                        /** @var Response $resp */
                        $resp = $this->createLemonwayTx($dcd->getAmount(), $dcd->getAccount(), $dcd->getExchanger());
                        $this->log($output, "RESP: " . print_r($resp, true));

                        # if received is ok
                        if (strpos($resp, 'received') !== false) {

                            if(preg_match("/ID: ([a-zA-Z0-9]+)/", $resp, $matches)) {
                                $txId = $matches[1];

                                /** @var Transaction $tx */
                                $tx = $txRepo->find($txId);
                                $output->writeln("TX(id): " . $tx->getId());
                                $dcd->setTransaction($tx);
                                $em->persist($dcd); $em->flush();


                                $dcd->setStatus(DelegatedChangeData::STATUS_SUCCESS);
                                $em->persist($dcd); $em->flush();
                                $sendParams = [
                                    'to' => $dcd->getExchanger()->getCIF(),
                                    'amount' => number_format($dcd->getAmount()/100, 2)
                                ];
                                /** @var LemonWayMethod $lemonMethod */
                                $lemonMethod = $this->getContainer()->get('net.telepay.out.lemonway.v1');

                                # send the money to the exchanger's LemonWay account
                                $lemonMethod->send($sendParams);
                            }
                            else {
                                $this->log($output, "Failed to fetch txid");
                                $dcd->setStatus(DelegatedChangeData::STATUS_ERROR);
                                $em->persist($dcd); $em->flush();
                            }
                        }
                        else {
                            $dcd->setStatus(DelegatedChangeData::STATUS_ERROR);
                            $em->persist($dcd); $em->flush();
                            $this->log(
                                $output,
                                "Transaction creation failed: status_code=" . $resp->getStatusCode(),
                                DelegatedChangeV2Command::SEVERITY_CRITICAL
                            );
                        }
                    } catch (HttpException $e){
                        $this->log(
                            $output,
                            "Transaction creation failed: " . $e->getMessage(),
                            DelegatedChangeV2Command::SEVERITY_CRITICAL
                        );
                    }

                }
                $this->log($output, "Done entry: " . $dcd->getId());
            }
            $dc->setStatus(DelegatedChange::STATUS_FINISHED);
            $em->persist($dc); $em->flush();
            $this->log($output, "Done delegated change: " . $dc->getId());
        }
        $this->log($output, "Finish");
    }

    /**
     * @param $url
     * @param $cardHolder
     * @param $pan
     * @param $expiryMonth
     * @param $expiryYear
     * @param $cvv2
     * @return bool
     */
    private function launchBot($url, $cardHolder, $pan, $expiryMonth, $expiryYear, $cvv2){
        $args = "$url $cardHolder $pan $expiryMonth $expiryYear $cvv2";
        $botScript = $this->get('kernel')->getRootDir() . "/../docker/prod/cron/pay-cli.py";
        $botProcess = new Process("python3 " . $botScript . " " . $args);
        $botProcess->run();
        return $botProcess->isSuccessful();
    }
}