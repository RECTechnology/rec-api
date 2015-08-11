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

class ExpirePosCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:pos:expire')
            ->setDescription('Check pos expired transactions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $service_cname = 'pos';

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('service')->equals($service_cname)
            ->field('status')->equals('created')
            ->getQuery();

        $contador = 0;
        foreach($qb->toArray() as $transaction){

            //todo pillar los parametros de la tpv
            $pos_id = $transaction->getPosId();
            $posRepo = $em->getRepository('TelepayFinancialApiBundle:POS')->findBy(array(
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
                $transaction->setStatus('expired');
                $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
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