<?php

namespace App\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use App\FinancialApiBundle\Entity\UserWallet;
use App\FinancialApiBundle\Financial\Currency;

class ListBmincomeTxCommand extends ContainerAwareCommand{
    protected function configure(){
        $this
            ->setName('rec:bmin:list')
            ->setDescription('Check rec balances')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $em = $this->getContainer()->get('doctrine')->getManager();
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $groupList = $em->getRepository('FinancialApiBundle:Group')->findBy(array(
            'subtype' => 'BMINCOME'
        ));

        $output->writeln("Rec");
        $output->writeln("-----------");
        foreach ( $groupList as $account ){
            $wallet = $account->getWallet('rec');
            $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
                ->field('service')->equals('rec')
                ->field('type')->equals('in')
                ->field('group')->equals($account->getId())
                ->getQuery();
            foreach ($qb->toArray() as $transaction) {
                $data = $transaction->getPayInInfo();
                $created = $transaction->getCreated();
                $text = $account->getId() . "," . $wallet->getBalance()/100000000 . "," . $account->getCIF() . "," . $transaction->getMethod() . "," . $transaction->getStatus() . "," . ($transaction->getAmount()/100) . "," . $data['concept']  . "," . $created->format('Y-m-d H:i:s');
                $output->writeln($text);
            }
        }

        $output->writeln("-----------");
        $output->writeln("Lemon");
        $output->writeln("-----------");

        foreach ( $groupList as $account ){
            $wallet = $account->getWallet('rec');
            $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
                ->field('service')->equals('lemonway')
                ->field('type')->equals('in')
                ->field('group')->equals($account->getId())
                ->getQuery();
            foreach ($qb->toArray() as $transaction) {
                $created = $transaction->getCreated();
                $data = $transaction->getPayInInfo();
                $text = $account->getId() . "," . $wallet->getBalance()/100000000 . "," . $account->getCIF() . "," . $transaction->getMethod() . "," . $transaction->getStatus() . "," . $transaction->getAmount()/100 . "," . $data['concept'] . "," . $created->format('Y-m-d H:i:s');
                $output->writeln($text);
            }
        }
        $output->writeln("-----------");
    }
}