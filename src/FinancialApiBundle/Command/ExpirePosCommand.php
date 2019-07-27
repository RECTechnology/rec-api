<?php
namespace App\FinancialApiBundle\Command;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Timestamp;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\DateTime;
use App\FinancialApiBundle\DependencyInjection\App\Commons\FeeDeal;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Exchange;

class ExpirePosCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:pos:expire')
            ->setDescription('Check pos expired transactions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $service_cname = array('POS-PNP', 'POS-SAFETYPAY', 'POS-SABADELL');

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();

        $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
            ->field('method')->in($service_cname)
            ->field('status')->equals('created')
            ->field('currency')->equals('EUR')
            ->getQuery();

        $contador = 0;
        foreach($qb->toArray() as $transaction){

            $pos_id = $transaction->getPosId();
            $posRepo = $em->getRepository('FinancialApiBundle:POS')->findBy(array(
                'pos_id' =>  $pos_id
            ));

            $expired = false;

            if(!$posRepo){
                $expired = true;
            }else{
                $expires_in = $posRepo[0]->getExpiresIn();
                $created = $transaction->getCreated();
                $created = $created->getTimestamp();

                $actual = new \MongoDate();
                $actual = $actual->sec;

                $expires = $created + $expires_in;

                if($expires < $actual){
                    $expired = true;
                }
            }

            if($expired == true){
                $transaction->setStatus(Transaction::$STATUS_EXPIRED);
                $paymentInfo = $transaction->getPayInInfo();
                $paymentInfo['status'] = Transaction::$STATUS_EXPIRED;
//                $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                $transaction->setUpdated(new \MongoDate());
                $contador ++;
            }

            $dm->persist($transaction);
            $em->flush();

            $dm->flush();

        }

        $output->writeln('Expired: '.$contador.' pos transactions');
    }


}