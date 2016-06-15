<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Telepay\FinancialApiBundle\Document\Transaction;

class CheckHalcashCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:halcash:check')
            ->setDescription('Check halcash transactions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('method')->in(array('halcash_es', 'halcash_pl'))
            ->field('pay_out_info.status')->equals('sent')
            ->getQuery();

        $contador = 0;
        $contador_success = 0;
        foreach($qb->toArray() as $transaction){
            $contador ++;
            $paymentInfo = $transaction->getPayOutInfo();

            $previous_status = $paymentInfo['status'];
            $output->writeln('txid: '.$transaction->getId());
            $output->writeln('status: '.$paymentInfo['status']);

            $cashOutMethod = $this->getContainer()->get('net.telepay.out.'.$transaction->getMethod().'.v1');

            $paymentInfo = $cashOutMethod->getPayOutStatus($paymentInfo);

            if($previous_status != $paymentInfo['status']){
                $transaction->setPayOutInfo($paymentInfo);
                $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                $transaction->setUpdated(new \MongoDate());
                $dm->persist($transaction);
                $dm->flush();

                $contador_success ++;

            }

        }

        $dm->flush();

        $output->writeln('Halcash send transactions checked');
        $output->writeln('Total checked transactions: '.$contador);
        $output->writeln('Success transactions: '.$contador_success);
    }

}