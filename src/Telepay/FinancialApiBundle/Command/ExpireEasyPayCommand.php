<?php
namespace Telepay\FinancialApiBundle\Command;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Timestamp;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\DateTime;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\FeeDeal;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Exchange;

class ExpireEasyPayCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:easypay:expire')
            ->setDescription('Check easyPay expired transactions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $service_cname = 'easypay';

        $expires_in = 3600*24;

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repo=$em->getRepository('TelepayFinancialApiBundle:User');

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('service')->equals($service_cname)
            ->field('status')->equals('created')
            ->getQuery();

        $contador = 0;
        foreach($qb->toArray() as $transaction){

            $created = $transaction->getCreated();
            $created = $created->getTimestamp();

            $actual = new \MongoDate();
            $actual = $actual->sec;

            $expires = $created + $expires_in;

            if($expires < $actual){
                $transaction->setStatus('expired');
                $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                $transaction->setUpdated(new \MongoDate());
                $contador ++;
            }

            $dm->persist($transaction);
            $em->flush();

            $dm->flush();

        }

        $output->writeln('Expired: '.$contador.' easypay transactions');
    }

    public function check(Transaction $transaction){

        return $transaction;
    }

    public function sendEmail($subject, $body){

        $no_replay = $this->getContainer()->getParameter('no_reply_email');

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($no_replay)
            ->setTo(array(
                'pere@playa-almarda.es',
                'support@chip-chap.com'
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