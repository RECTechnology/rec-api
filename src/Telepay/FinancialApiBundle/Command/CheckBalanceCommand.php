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

class CheckBalanceCommand extends ContainerAwareCommand{
    protected function configure()
    {
        $this
            ->setName('rec:check:balance')
            ->setDescription('Check rec balances')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $em = $this->getContainer()->get('doctrine')->getManager();
        $groupList = $em->getRepository('TelepayFinancialApiBundle:Group')->findAll();
        $scale = 100000000;

        foreach ( $groupList as $account ){
            $wallet = $account->getWallet('rec');
            $address = $account->getRecAddress();
            $cryptoProvider = $this->getContainer()->get('net.telepay.in.rec.v1');
            $rec_balance = $cryptoProvider->getReceivedByAddress($address,0);
            $wallet->setBlockchainPending($rec_balance*$scale);
            $rec_balance = $cryptoProvider->getReceivedByAddress($address,1);
            $wallet->setBlockchain($rec_balance*$scale);
            $em->persist($wallet);
            $em->flush();
        }
    }
}