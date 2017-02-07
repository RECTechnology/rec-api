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
use Telepay\FinancialApiBundle\Document\Transaction;

class ExpireSepaInCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:sepa_in:expire')
            ->setDescription('Check sepa_in expired transactions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $method = 'sepa';

        $expires_in = 3600*48;

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('method')->equals($method)
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

            $dm->flush();

        }

        $output->writeln('Expired: '.$contador.' sepa_in transactions');
    }

    public function check(Transaction $transaction){

        return $transaction;
    }

}