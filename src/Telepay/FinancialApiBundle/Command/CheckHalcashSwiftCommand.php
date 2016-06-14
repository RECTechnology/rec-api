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

        //Only search sent transactions
        $search = 'sent';

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('type')->equals('swift')
            ->field('method_in')->equals('btc')
            ->field('method_out')->in(array('halcash_es', 'halcash_pl'))
            ->field('status')->equals('success')
            ->where("function(){
                    if (typeof this.pay_out_info.status !== 'undefined') {
                        if(String(this.pay_out_info.status).indexOf('$search') > -1){
                            return true;
                        }
                    }
                    return false;
                }")
            ->getQuery();

        $contador = 0;

        $output->writeln('CHECKING WITHDRAWN HALCASH TRANSACTIONS');
        foreach($qb->toArray() as $transaction){

            $paymentInfo = $transaction->getPayOutInfo();

            $previous_status = $paymentInfo['status'];
            $output->writeln('txid: '.$transaction->getId().' prev status: '.strtoupper($paymentInfo['status']));

            $transaction = $this->check($transaction);

            switch ($transaction->getPayOutInfo()['status']){
                case 'cancelled':
                    $transaction->setStatus(Transaction::$STATUS_CANCELLED);
                    break;
                case 'expired':
                    $transaction->setStatus(Transaction::$STATUS_EXPIRED);
                    break;
                case 'locked':
                    $transaction->setStatus(Transaction::$STATUS_LOCKED);
                    break;
                case 'review':
                    $transaction->setStatus(Transaction::$STATUS_REVIEW);
                    break;
                default:
                    break;
            }

            $output->writeln('NEW STATUS '.$transaction->getPayOutInfo()['status']);

            $dm->persist($transaction);
            $dm->flush();

            if($previous_status != $transaction->getPayOutInfo()['status']){
                $contador ++;
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
        $output->writeln('Total changed transactions: '.$contador);
    }

    public function check(Transaction $transaction){

        $logger = $this->getContainer()->get('logger');

        $payOutInfo = $this->getContainer()->get('net.telepay.out.'.$transaction->getMethodOut().'.v1')->getPayOutStatus($transaction->getPayOutInfo());

        $transaction->setPayOutInfo($payOutInfo);
        $logger->info('HALCASH->check by cron');
        $logger->info('HALCASH: ticket-> '.$transaction->getId().', status->'.$transaction->getPayOutInfo()['status']);

        return $transaction;
    }

    private function sendEmail($subject, $body){

        $no_replay = $this->getContainer()->getParameter('no_reply_email');

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($no_replay)
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