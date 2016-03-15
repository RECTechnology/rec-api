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

        $service_cname = 'halcash_send';

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repo = $em->getRepository('TelepayFinancialApiBundle:User');

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('service')->equals($service_cname)
            ->field('status')->equals('created')
            ->getQuery();

        $resArray = [];
        $contador = 0;
        $contador_success = 0;
        foreach($qb->toArray() as $transaction){
            $contador ++;
            $data = $transaction->getDataIn();
            $resArray [] = $transaction;

            $previous_status = $transaction->getStatus();
            $output->writeln('txid: '.$transaction->getId());
            $output->writeln('status: '.$transaction->getStatus());

            $checked_transaction = $this->check($transaction);

            if($previous_status != $checked_transaction->getStatus()){
                $checked_transaction = $this->getContainer()->get('notificator')->notificate($checked_transaction);
                $checked_transaction->setUpdated(new \MongoDate());

            }

            $dm->persist($checked_transaction);
            $em->flush();

            $dm->flush();

        }

        $dm->flush();

        $output->writeln('Halcash send transactions checked');
        $output->writeln('Total checked transactions: '.$contador);
        $output->writeln('Success transactions: '.$contador_success);
    }

    public function check(Transaction $transaction){

        $ticket = $transaction->getDataOut()['halcashticket'];

        $status = $this->getContainer()->get('net.telepay.provider.halcash')->status($ticket);

        if($status['errorcode'] == 0){

            switch($status['estadoticket']){
                case 'Autorizada':
                    $transaction->setStatus('created');
                    break;
                case 'Preautorizada':
                    $transaction->setStatus('created');
                    break;
                case 'Anulada':
                    $transaction->setStatus('cancelled');
                    $this->sendEmail('Check hal --> '.$transaction->getStatus(), 'Transaccion '.$status['estadoticket']);
                    break;
                case 'BloqueadaPorCaducidad':
                    $transaction->setStatus('expired');
                    $transaction->setDebugData(array(
                        'estadoticket'  =>  $status['estadoticket']
                    ));
                    $this->sendEmail('Check hal --> '.$transaction->getStatus(), 'Transaccion '.$status['estadoticket']);
                    break;
                case 'BloqueadaPorReintentos':
                    $transaction->setStatus('error');
                    $transaction->setDebugData(array(
                        'estadoticket'  =>  $status['estadoticket']
                    ));
                    $this->sendEmail('Check hal --> '.$transaction->getStatus(), 'Transaccion '.$status['estadoticket']);
                    break;
                case 'Devuelta':
                    $transaction->setStatus('returned');
                    $transaction->setDebugData(array(
                        'estadoticket'  =>  $status['estadoticket']
                    ));
                    $this->sendEmail('Check hal --> '.$transaction->getStatus(), 'Transaccion '.$status['estadoticket']);
                    break;
                case 'Dispuesta':
                    $transaction->setStatus('success');
                    break;
                case 'EstadoDesconocido':
                    $transaction->setStatus('unknown');
                    $transaction->setDebugData(array(
                        'estadoticket'  =>  $status['estadoticket']
                    ));
                    $this->sendEmail('Check hal --> '.$transaction->getStatus(), 'Transaccion '.$status['estadoticket']);
                    break;
            }

        }

        $logger = $this->getContainer()->get('logger');
        $logger->info('HALCASH->check by cron');
        $logger->info('HALCASH: ticket-> '.$ticket.', status->'.$status['estadoticket']);

        return $transaction;
    }

    private function sendEmail($subject, $body){

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom('no-reply@chip-chap.com')
            ->setTo(array(
                'pere@chip-chap.com'
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