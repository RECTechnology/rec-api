<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Telepay\FinancialApiBundle\Document\Transaction;

class CheckHalcashSwiftCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:halcash:swift-check')
            ->setDescription('Check halcash for swift transactions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('type')->equals('swift')
            ->field('method_in')->equals('btc')
            ->field('method_out')->in(array('halcash_es', 'halcash_pl'))
            ->field('status')->equals('success')
            ->getQuery();

        $contador = 0;

        foreach($qb->toArray() as $transaction){
            $contador ++;
            $paymentInfo = $transaction->getPayOutInfo();

            $previous_status = $paymentInfo['status'];
            $output->writeln('txid: '.$transaction->getId());
            $output->writeln('status: '.$paymentInfo['status']);

            $transaction = $this->check($transaction);

            $dm->persist($transaction);
            $dm->flush();

            if($previous_status != $transaction->getPayOutInfo()['status']){
                $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                $transaction->setUpdated(new \MongoDate());

            }

            $dm->persist($transaction);
            $dm->flush();

            if($transaction->getPayOutInfo()['status'] == Transaction::$STATUS_EXPIRED ||
                $transaction->getPayOutInfo()['status'] == Transaction::$STATUS_LOCKED){
                $this->sendEmail('Halcash transaction expired or locked by retries', $transaction->getId());
            }

        }

        $dm->flush();

        $output->writeln('Halcash send transactions checked');
        $output->writeln('Total checked transactions: '.$contador);
    }

    public function check(Transaction $transaction){

        $logger = $this->getContainer()->get('logger');

        $payOutInfo = $this->getContainer()->get('net.telepay.out.'.$transaction->getMethodOut().'.v1')->status($transaction->getPayOutInfo());

        $transaction->setPayOutInfo($payOutInfo);
        $logger->info('HALCASH->check by cron');
        $logger->info('HALCASH: ticket-> '.$transaction->getId().', status->'.$transaction->getPayOutInfo()['status']);

        return $transaction;
    }

    private function sendEmail($subject, $body){

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom('no-reply@chip-chap.com')
            ->setTo(array(
                'pere@chip-chap.com',
                'cto@chip-chap.com'
            ))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:support.html.twig',
                        array(
                            'message'        =>  $body
                        )
                    )
            )
            ->setContentType('text/html');

        $this->getContainer()->get('mailer')->send($message);
    }

}