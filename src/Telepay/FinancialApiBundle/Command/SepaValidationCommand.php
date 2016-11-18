<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Telepay\FinancialApiBundle\Document\Transaction;

class SepaValidationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:sepa:validation')
            ->setDescription('Validate all gestioned transactions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('method')->equals(array('sepa', 'transfer'))
            ->field('type')->equals('out')
            ->field('status')->equals('sending')
            ->field('pay_out_info.gestioned')->equals(true)
            ->getQuery()
            ->execute();

        $contador = 0;
        $contador_success = 0;
        foreach($qb->toArray() as $transaction){
            $contador ++;
            $paymentInfo = $transaction->getPayOutInfo();

            $output->writeln('txid: '.$transaction->getId());
            $output->writeln('status: '.$paymentInfo['status']);

            $paymentInfo['status'] = Transaction::$STATUS_SUCCESS;
            $transaction->setStatus(Transaction::$STATUS_SUCCESS);

            $dm->persist($transaction);
            $dm->flush();

        }


        $qb_swift = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('method_out')->equals('sepa')
            ->field('type')->equals('swift')
            ->field('status')->equals('sending')
            ->field('pay_out_info.gestioned')->equals(true)
            ->getQuery()
            ->execute();

        foreach($qb_swift->toArray() as $transaction){
            $contador ++;
            $paymentInfo = $transaction->getPayOutInfo();

            $output->writeln('txid: '.$transaction->getId());
            $output->writeln('status: '.$paymentInfo['status']);

            $paymentInfo['status'] = Transaction::$STATUS_SUCCESS;
            $transaction->setStatus(Transaction::$STATUS_SUCCESS);

            $dm->persist($transaction);
            $dm->flush();

        }


        $output->writeln('Sepa transactions validated');
        $output->writeln('Total checked transactions: '.$contador);
        $output->writeln('Success transactions: '.$contador_success);
    }

}