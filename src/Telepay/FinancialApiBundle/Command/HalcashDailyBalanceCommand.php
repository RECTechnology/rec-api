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

    protected function execute(InputInterface $input, OutputInterface $output){
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $start_time = new \MongoDate(strtotime('-1day'));//date('Y-m-d 00:00:00')
        $finish_time = new \MongoDate();

        $services_hal = array(
            'halcash_es' => 0,
            'halcash_pl' => 0
        );

        foreach($services_hal as $service => $count){
            $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('type')->equals('swift')
                ->field('method_out')->equals($service)
                ->field('status')->equals('success')
                ->field('created')->gte($start_time)
                ->field('created')->lte($finish_time)
                ->getQuery();

            foreach($qb->toArray() as $transaction){
                $output->writeln('nueva transaccion');
                $paymentInfo = $transaction->getPayOutInfo();
                if($paymentInfo['status'] == 'sent' || $paymentInfo['status'] == 'withdrawn'){
                    $services_hal[$service] += $transaction->getAmount();
                }
            }
        }

        $services_out = array(
            'cryptocapital' => 0,
            'sepa' => 0
        );

        foreach($services_out as $service => $count){
            $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('type')->equals('swift')
                ->field('method_out')->equals($service)
                ->field('status')->equals('success')
                ->field('updated')->gte($start_time)
                ->field('updated')->lte($finish_time)
                ->getQuery();

            foreach($qb->toArray() as $transaction){
                $output->writeln('nueva transaccion');
                $paymentInfo = $transaction->getPayOutInfo();
                if($paymentInfo['status'] == 'sent' || $paymentInfo['status'] == 'withdrawn'){
                    $services_out[$service] += $transaction->getAmount();
                }
            }
        }

        $services_in = array(
            'easypay' => 0,
            'paynet_reference' => 0,
            'safetypay' => 0
        );

        foreach($services_in as $service => $count){
            $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('type')->equals('swift')
                ->field('method_in')->equals($service)
                ->field('status')->equals('success')
                ->field('updated')->gte($start_time)
                ->field('updated')->lte($finish_time)
                ->getQuery();

            foreach($qb->toArray() as $transaction){
                $paymentInInfo = $transaction->getPayInInfo();
                if($paymentInfo['status'] == 'sent' || $paymentInfo['status'] == 'withdrawn'){
                    $services_in[$service] += $paymentInInfo['amount'];
                }
            }
        }

        $this->sendEmail(
            'Informe de transacciones de hal',
            'Total Transacciones:
             halcash últimas 24 horas: ' . $services_hal['halcash_es']/100 . ' EUR.
             ' . $services_hal['halcash_pl']/100 . ' PLN.
             Cryptocapital: ' . $services_out['cryptocapital']/100 . ' EUR.
             Sepa: ' . $services_out['sepa']/100 . ' EUR.
             Paynet: ' . $services_in['paynet_reference']/100 . ' MXN.
             Safetypay: ' . $services_in['safetypay']/100 . ' EUR.
             Easypay: ' . $services_in['easypay']/100 . ' EUR.
             '
        );

        $output->writeln('Informe enviado');
    }


    private function sendEmail($subject, $body){

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom('no-reply@chip-chap.com')
            ->setTo(array(
                'volume@chip-chap.com'
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