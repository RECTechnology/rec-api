<?php

namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

class ListRecTxCommand extends ContainerAwareCommand{
    protected function configure(){
        $this
            ->setName('rec:tx:list')
            ->setDescription('List rec tx')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $em = $this->getContainer()->get('doctrine')->getManager();
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $output->writeln("sender_id,s_type,s_subtype,receiver_id,r_type,r_subtype,coin,internal,status,amount,date");
        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('service')->equals('rec')
            ->field('type')->equals('out')
            ->getQuery();
        foreach ($qb->toArray() as $transaction) {
            $sender = $em->getRepository('TelepayFinancialApiBundle:Group')->findOneBy(array(
                'id' => $transaction->getGroup()
            ));

            $payment_info = $transaction->getPayOutInfo();
            $address = $payment_info['address'];
            $receiver = $em->getRepository('TelepayFinancialApiBundle:Group')->findOneBy(array(
                'rec_address' => $address
            ));

            $created = $transaction->getCreated();
            $output->writeln($sender->getId() . "," . $sender->getType() . "," . $sender->getSubtype() . "," .
                $receiver->getId() . "," . $receiver->getType() . "," . $receiver->getSubtype()  . "," .
                $transaction->getMethod() . "," . $transaction->getInternal() . "," . $transaction->getStatus() . "," .
                ($transaction->getAmount()/100000000) . "," . $created->format('Y-m-d H:i:s'));
        }
    }
}