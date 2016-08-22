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

class MigrateErrorTransactionCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:update:error-trans')
            ->setDescription('Update error transaction to define pay-in-info = error')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $transactions = $dm->getRepository("TelepayFinancialApiBundle:Transaction")->findBy(array(
            'type'  =>  'swift',
            'status' => 'error'
        ));

        $output->writeln('Migrating '.count($transactions).' transactions...');
        $counterTransactions = 0;
        foreach($transactions as $transaction){
            $dataIn = $transaction->getDataIn();

            if(!$dataIn['status']){
                $pay_in_info = array(
                  'status' => 'error'
                );
            }

            $transaction->setPayInInfo($pay_in_info);
            $dm->persist($transaction);
            $dm->flush($transaction);
            $counterTransactions ++;
        }

        $output->writeln($counterTransactions.' transactions updated');

        $output->writeln('All done');
    }

}