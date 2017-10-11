<?php
namespace Telepay\FinancialApiBundle\Command;

use DateTime;
use Swift_Attachment;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\LimitAdder;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Financial\Currency;

class SendPylonMailCommand extends SyncronizedContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:pylon:send:mail')
            ->setDescription('Send mail to Pylon users')
        ;
    }

    protected function executeSyncronized(InputInterface $input, OutputInterface $output)
    {

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('type')->equals('swift')
            ->field('status')->equals('success')
            ->field('method_out')->equals('eth')
            ->getQuery();

        $output->writeln('Preparing to send '.count($qb).' mails');

        foreach($qb->toArray() as $transaction){

            if( $transaction->getEmailNotification() != ""){
                $email = $transaction->getEmailNotification();
                $output->writeln('Sending email to '.$email);

                if(isset($transaction->getPayInInfo()['reference'])) {
                    $ticket = $transaction->getPayInInfo()['reference'];
                }
                else{
                    $ticket = $transaction->getId();
                }
                $body = array(
                    'reference' =>  $ticket,
                    'created'   =>  $transaction->getCreated()->format('Y-m-d H:i:s'),
                    'concept'   =>  'BUY ETH ' . $ticket,
                    'amount'    =>  $transaction->getPayInInfo()['amount']/pow(10,$transaction->getPayInInfo()['scale']),
                    'crypto_amount' => $transaction->getPayOutInfo()['amount']/pow(10,$transaction->getScale()),
                    'tx_id'        =>  $transaction->getPayOutInfo()['txid'],
                    'id'        =>  $ticket,
                    'address'   =>  $transaction->getPayOutInfo()['address'],
                    'currency_in'   =>  $transaction->getPayInInfo()['currency'],
                    'currency_out'   =>  $transaction->getPayOutInfo()['currency']
                );

                $this->_sendTicket($body, $email, $ticket, $transaction->getMethodOut());
            }


        }
    }

    private function _sendTicket($body, $email, $ref, $method_out){
        $html = $this->getContainer()->get('templating')->render('TelepayFinancialApiBundle:Email:ticket' . $method_out . '.html.twig', $body);

        $marca = array(
            "btc" => "Chip-Chap",
            "fac" => "Fairtoearth",
            "crea"  =>  "Chip-Chap",
            "eth"   =>  "Pylon"
        );
        $dompdf = $this->getContainer()->get('slik_dompdf');
        $dompdf->getpdf($html);
        $pdfoutput = $dompdf->output();

        $no_replay = $this->getContainer()->getParameter('no_reply_email');

        $message = \Swift_Message::newInstance()
            ->setSubject($marca[$method_out] . 'Ticket ref: '.$ref)
            ->setFrom($no_replay)
            ->setTo(array(
                $email
            ))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:ticket' . $method_out . '.html.twig',
                        $body
                    )
            )
            ->setContentType('text/html')
            ->attach(Swift_Attachment::newInstance($pdfoutput, $ref.'-'.$body["id"].'.pdf'));

        $this->getContainer()->get('mailer')->send($message);
    }

}