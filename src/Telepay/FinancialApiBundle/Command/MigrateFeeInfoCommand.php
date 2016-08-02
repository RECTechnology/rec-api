<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/15/14
 * Time: 1:27 PM
 */

namespace Telepay\FinancialApiBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateFeeInfoCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:migrate:fee-info')
            ->setDescription('Migrate feeinfo')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $transactions = $dm->getRepository("TelepayFinancialApiBundle:Transaction")->findBy(array(
            'type'  =>  'fee'
        ));

        $output->writeln('Migrating '.count($transactions).' transactions...');
        $counterTransactions = 0;
        foreach($transactions as $transaction){
            $dataIn = $transaction->getDataIn();
            $dataIn['status'] = $transaction->getStatus();
            $dataIn['scale'] = $transaction->getScale();
            $dataIn['currency'] = $transaction->getCurrency();
            $transaction->setFeeInfo($dataIn);
            $dm->persist($transaction);
            $dm->flush($transaction);
            $counterTransactions ++;
        }

        $output->writeln($counterTransactions.' transactions updated');

        $output->writeln('All done');
    }

}