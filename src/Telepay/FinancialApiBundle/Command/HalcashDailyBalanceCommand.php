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

        $methods_hal = array(
            'halcash_es' => 0,
            'halcash_pl' => 0
        );

        $services_hal_refund = array(
            'halcash_es' => 0,
            'halcash_pl' => 0
        );

        $methods_hal_refund = array(
            'halcash_es' => 0,
            'halcash_pl' => 0
        );

        foreach($services_hal as $service => $count){
            //swift success transactions halcash
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

            //swift refund transactions halcash
            $qbRefund = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('type')->equals('swift')
                ->field('method_out')->equals($service)
                ->field('status')->equals('refund')
                ->field('updated')->gte($start_time)
                ->field('updated')->lte($finish_time)
                ->getQuery();

            foreach($qbRefund->toArray() as $transaction){
                $output->writeln('nueva transaccion en refund');
                $paymentInfo = $transaction->getPayInInfo();
                if($paymentInfo['status'] == 'refund'){
                    $services_hal_refund[$service] += $transaction->getAmount();
                }
            }

            //methods success halcash
            $qbMethod = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('type')->equals('out')
                ->field('method')->equals($service)
                ->field('status')->equals('success')
                ->field('created')->gte($start_time)
                ->field('created')->lte($finish_time)
                ->getQuery();

            foreach($qbMethod->toArray() as $transaction){
                $output->writeln('nueva transaccion method');
                $paymentInfo = $transaction->getPayOutInfo();
                if($paymentInfo['status'] == 'sent' || $paymentInfo['status'] == 'withdrawn'){
                    $methods_hal[$service] += $transaction->getAmount();
                }
            }

            //methods canceled halcash
            $qbMethodRefund = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('type')->equals('out')
                ->field('method')->equals($service)
                ->field('status')->equals('cancelled')
                ->field('updated')->gte($start_time)
                ->field('updated')->lte($finish_time)
                ->getQuery();

            foreach($qbMethodRefund->toArray() as $transaction){
                $output->writeln('nueva transaccion en refund');
                $paymentInfo = $transaction->getPayOutInfo();
                if($paymentInfo['status'] == 'cancelled'){
                    $methods_hal_refund[$service] += $transaction->getAmount();
                }
            }

        }

        $services_out = array(
            'cryptocapital' => 0,
            'sepa' => 0
        );

        $methods_out = array(
            'cryptocapital' => 0,
            'sepa' => 0
        );

        foreach($services_out as $service => $count){
            //swift success transactions cryptocapital
            if($service == 'cryptocapital'){
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

                //method success transaction cryptocapital
                $qbMethod = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                    ->field('type')->equals('out')
                    ->field('method')->equals($service)
                    ->field('status')->equals('success')
                    ->field('updated')->gte($start_time)
                    ->field('updated')->lte($finish_time)
                    ->getQuery();

                foreach($qbMethod->toArray() as $transaction){
                    $output->writeln('nueva transaccion');
                    $paymentInfo = $transaction->getPayOutInfo();
                    if($paymentInfo['status'] == 'sent' || $paymentInfo['status'] == 'withdrawn'){
                        $methods_out[$service] += $transaction->getAmount();
                    }
                }
            }else{
                //swift success transactions sepa_out
                $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                    ->field('type')->equals('swift')
                    ->field('method_out')->equals($service)
                    ->field('status')->equals('sending')
                    ->field('created')->gte($start_time)
                    ->field('created')->lte($finish_time)
                    ->getQuery();

                foreach($qb->toArray() as $transaction){
                    $output->writeln('nueva transaccion');
                    $paymentInfo = $transaction->getPayOutInfo();
                    if($paymentInfo['status'] == 'sending'){
                        $services_out[$service] += $transaction->getAmount();
                    }
                }

                //method success transaction sepa_out
                $qbMethod = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                    ->field('type')->equals('out')
                    ->field('method')->equals($service)
                    ->field('status')->equals('success')
                    ->field('updated')->gte($start_time)
                    ->field('updated')->lte($finish_time)
                    ->getQuery();

                foreach($qbMethod->toArray() as $transaction){
                    $output->writeln('nueva transaccion');
                    $paymentInfo = $transaction->getPayOutInfo();
                    if($paymentInfo['status'] == 'sent' || $paymentInfo['status'] == 'withdrawn'){
                        $methods_out[$service] += $transaction->getAmount();
                    }
                }
            }

        }

        $services_in = array(
            'easypay' => 0,
            'paynet_reference' => 0,
            'safetypay' => 0
        );

        $methods_in = array(
            'easypay' => 0,
            'paynet_reference' => 0,
            'safetypay' => 0
        );

        foreach($services_in as $service => $count){
            //swift in transactions
            $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('type')->equals('swift')
                ->field('method_in')->equals($service)
                ->field('status')->equals('success')
                ->field('updated')->gte($start_time)
                ->field('updated')->lte($finish_time)
                ->getQuery();

            foreach($qb->toArray() as $transaction){
                $paymentInfo = $transaction->getPayInInfo();
                if($paymentInfo['status'] == 'success'){
                    if($service == 'safetypay'){
                        $services_in[$service] += $paymentInfo['mxn_amount'];
                    }else{
                        $services_in[$service] += $paymentInfo['amount'];
                    }

                }
            }

            //methods in transactions
            $qbMethod = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('type')->equals('in')
                ->field('method')->equals($service)
                ->field('status')->equals('success')
                ->field('updated')->gte($start_time)
                ->field('updated')->lte($finish_time)
                ->getQuery();

            foreach($qbMethod->toArray() as $transaction){
                $paymentInfo = $transaction->getPayInInfo();
                if($paymentInfo['status'] == 'success'){
                    if($service == 'safetypay'){
                        $methods_in[$service] += $paymentInfo['mxn_amount'];
                    }else{
                        $methods_in[$service] += $paymentInfo['amount'];
                    }

                }
            }
        }

        $cryptos_in = array(
            'btc' => 0,
            'fac' => 0
        );

        $cryptos_out = array(
            'btc' => 0,
            'fac' => 0
        );

        foreach($cryptos_in as $crypto => $count){
            //methods in transactions cryptos
            $qbIn = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('type')->equals('in')
                ->field('method')->equals($crypto)
                ->field('status')->equals('success')
                ->field('updated')->gte($start_time)
                ->field('updated')->lte($finish_time)
                ->getQuery();

            foreach($qbIn->toArray() as $transaction){
                $paymentInfo = $transaction->getPayInInfo();
                if($paymentInfo['status'] == 'success'){
                    $cryptos_in[$crypto] += $paymentInfo['amount'];

                }
            }

            //methods out transactions cryptos
            $qbOut = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('type')->equals('out')
                ->field('method')->equals($crypto)
                ->field('status')->equals('success')
                ->field('updated')->gte($start_time)
                ->field('updated')->lte($finish_time)
                ->getQuery();

            foreach($qbOut->toArray() as $transaction){
                $paymentInfo = $transaction->getPayOutInfo();
                if($paymentInfo['status'] == 'success'){
                    $cryptos_out[$crypto] += $paymentInfo['amount'];

                }
            }
        }

        $swiftArray = array(
            'Halcash_es'    =>  $services_hal['halcash_es']/100 . ' EUR.',
            'Halcash_pl'    =>  $services_hal['halcash_pl']/100 . ' PLN.',
            'Halcash_es_refund' =>  $services_hal_refund['halcash_es']/100 .' EUR',
            'Halcash_pl_refund' =>  $services_hal_refund['halcash_pl']/100 .'PLN',
            'Cryptocapital' =>  $services_out['cryptocapital']/100 . ' EUR.',
            'Sepa'  =>  $services_out['sepa']/100 . ' EUR.',
            'Paynet'    =>  $services_in['paynet_reference']/100 . ' MXN.',
            'Safetypay' =>  $services_in['safetypay']/100 . ' MXN.',
            'Easypay'   =>  $services_in['easypay']/100 . ' EUR.'
        );

        $cashOutArray = array(
            'Halcash_es'    =>  $methods_hal['halcash_es']/100 . ' EUR.',
            'Halcash_pl'    =>  $methods_hal['halcash_pl']/100 . ' PLN.',
            'Cryptocapital' =>  $methods_out['cryptocapital']/100 . ' EUR.',
            'Sepa'          =>  $methods_out['sepa']/100 . ' EUR.',
            'Bitcoin'       =>  $cryptos_out['btc']/100000000 . ' BTC.',
            'Faircoin'      =>  $cryptos_out['fac']/100000000 . ' FAC.'
        );

        $cashInArray = array(
            'Paynet'    =>  $methods_in['paynet_reference']/100 . ' MXN.',
            'SafetyPay' =>  $methods_in['safetypay']/100 . ' MXN.',
            'Easypay'   =>  $methods_in['easypay']/100 . ' EUR.',
            'Bitcoin'   =>  $cryptos_in['btc']/100000000 . ' BTC.',
            'Faircoin'   =>  $cryptos_in['fac']/100000000 . ' BTC.',
        );

//        $body = array(
//            'Total Transacciones SWIFT:',
//            'halcash últimas 24 horas: ' . $services_hal['halcash_es']/100 . ' EUR.',
//            $services_hal['halcash_pl']/100 . ' PLN.',
//            'halcash refund: ' . $services_hal_refund['halcash_es']/100 . ' EUR.',
//            $services_hal_refund['halcash_pl']/100 . ' PLN.',
//            'Cryptocapital: ' . $services_out['cryptocapital']/100 . ' EUR.',
//            'Sepa: ' . $services_out['sepa']/100 . ' EUR.',
//            'Paynet: ' . $services_in['paynet_reference']/100 . ' MXN.',
//            'Safetypay: ' . $services_in['safetypay']/100 . ' MXN.',
//            'Easypay: ' . $services_in['easypay']/100 . ' EUR.',
//            'Total transacciones METHODS:',
//            'halcash_es: ' . $methods_hal['halcash_es']/100 . ' EUR.',
//            'halcash_pl: ' . $methods_hal['halcash_pl']/100 . ' PLN.',
//            'Cryptocapital-out: ' . $methods_out['cryptocapital']/100 . ' EUR.',
//            'Sepa-out: ' . $methods_out['sepa']/100 . ' EUR.',
//            'Paynet: ' . $methods_in['paynet_reference']/100 . ' MXN.',
//            'Safetypay: ' . $methods_in['safetypay']/100 . ' MXN.',
//            'Easypay: ' . $methods_in['easypay']/100 . ' EUR.',
//            'Bitcoin-in: ' . $cryptos_in['btc']/100 . ' BTC.',
//            'Faircoin-in: ' . $cryptos_in['fac']/100 . ' FAC.',
//            'Bitcoin-out: ' . $cryptos_out['btc']/100 . ' BTC.',
//            'Faircoin-out: ' . $cryptos_out['fac']/100 . ' FAC.'
//        );


        $this->sendEmail(
            'Informe de transacciones de hal',
            $swiftArray,
            $cashInArray,
            $cashOutArray
        );

        $output->writeln('Informe enviado');
    }


    private function sendEmail($subject, $swift, $cashIn, $cashOut){

        $no_replay = $this->getContainer()->getParameter('no_reply_email');

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($no_replay)
            ->setTo(array(
                $this->getContainer()->getParameter('volume_email')
            ))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:support.html.twig',
                        array(
                            'swifts'        =>  $swift,
                            'cash_ins'   =>  $cashIn,
                            'cash_outs'  =>  $cashOut
                        )
                    )
            )
            ->setContentType('text/html');

        $this->getContainer()->get('mailer')->send($message);
    }

}