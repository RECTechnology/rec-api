<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\FeeDeal;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\LimitAdder;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Exchange;
use Telepay\FinancialApiBundle\Financial\Currency;

class HalcashDailyBalanceCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:halcash:daily-balance')
            ->setDescription('Check daily halcash transactions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();

        $start_time = new \MongoDate(strtotime('-1day'));//date('Y-m-d 00:00:00')
        $finish_time = new \MongoDate();

        $services = array(
            'halcash_es',
            'halcash_pl'
        );

        $total_transactions_es = 0;
        $total_transactions_pl = 0;

        foreach($services as $service){
            $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('type')->equals('swift')
                ->field('method_out')->equals($service)
                ->field('status')->equals('success')
                ->field('created')->gte($start_time)
                ->field('created')->lte($finish_time)
                ->getQuery();

            $output->writeln(count($qb->toArray()).'... transactions to check');


            foreach($qb->toArray() as $transaction){
                $output->writeln('nueva transaccion');
                $paymentInfo = $transaction->getPayOutInfo();
                if($paymentInfo['status'] == 'sent' || $paymentInfo['status'] == 'withdrawn'){
                    if($service == 'halcash_es'){
                        $total_transactions_es = $total_transactions_es + $transaction->getAmount();
                    }else{
                        $total_transactions_pl = $total_transactions_pl + $transaction->getAmount();
                    }

                }
            }
        }

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('type')->equals('swift')
            ->field('method_in')->equals('easypay')
            ->field('status')->equals('success')
            ->field('updated')->gte($start_time)
            ->field('updated')->lte($finish_time)
            ->getQuery();

        $total_transactions_EP = 0;
        foreach($qb->toArray() as $transaction){
            $paymentInInfo = $transaction->getPayInInfo();
            $paymentOutInfo = $transaction->getPayOutInfo();
            if($paymentOutInfo['status'] == 'sent' || $paymentOutInfo['status'] == 'withdrawn'){
                $total_transactions_EP = $total_transactions_EP + $paymentInInfo['amount'];
            }
        }

        $this->sendEmail(
            'Informe de transacciones de hal',
            'Total Transacciones halcash últimas 24 horas: ' . $total_transactions_es/100 . ' EUR, ' . $total_transactions_pl/100 . ' PLN. Easypay: ' . $total_transactions_EP/100 . ' EUR'
        );

        $output->writeln('Informe enviado');
    }


    private function sendEmail($subject, $body){

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom('no-reply@chip-chap.com')
            ->setTo(array(
                'pere@chip-chap.com',
                'lluis@chip-chap.com',
                'cio@chip-chap.com',
                'cto@chip-chap.com'
            ))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:support.html.twig',
                        array(
                            'message'        =>  $body
                        )
                    )
            );

        $this->getContainer()->get('mailer')->send($message);
    }

}