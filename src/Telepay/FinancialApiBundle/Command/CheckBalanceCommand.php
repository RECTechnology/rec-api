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
            $rec_balance_0 = $cryptoProvider->getReceivedByAddress($address,0);
            $wallet->setBlockchainPending($rec_balance_0*$scale);
            $rec_balance_1 = $cryptoProvider->getReceivedByAddress($address,1);
            $wallet->setBlockchain($rec_balance_1*$scale);
            $output->writeln($account->getId() . ': ' . $wallet->getBalance() . " " . $rec_balance_0*$scale . " " . $rec_balance_1*$scale);
            if($wallet->getBalance() != $rec_balance_1*$scale){
                exec('curl -X POST -d "chat_id=-250635592&text=#ERROR BALANCE ' . $account->getId() . '" ' . '"https://api.telegram.org/bot787861588:AAFWCYdIiAoltb0IoM71jlmzq3AHh8kXSMs/sendMessage"');
            }
            $em->persist($wallet);
            $em->flush();
            sleep(20);
        }
    }
}