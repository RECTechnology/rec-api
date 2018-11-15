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
            ->addOption(
                'first_id',
                null,
                InputOption::VALUE_REQUIRED,
                'Define the fisrt group id',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $first = $input->getOption('first_id');
        $first = intval($first);

        $em = $this->getContainer()->get('doctrine')->getManager();
        $groupList = $em->getRepository('TelepayFinancialApiBundle:Group')->findAll();
        $scale = 100000000;

        foreach ( $groupList as $account ){
            if($account->getId() >= $first){
                $wallet = $account->getWallet('rec');
                $address = $account->getRecAddress();
                $cryptoProvider = $this->getContainer()->get('net.telepay.in.rec.v1');
                $rec_balance = $cryptoProvider->getReceivedByAddress($address,0);
                $rec_balance_0 = intval($rec_balance*$scale);
                $wallet->setBlockchainPending($rec_balance_0);
                $balance = $wallet->getBalance();
                $rec_balance = $cryptoProvider->getReceivedByAddress($address,1);
                $rec_balance_1 = intval($rec_balance*$scale);
                $wallet->setBlockchain($rec_balance_1);
                $output->writeln($account->getId() . ': ' . $balance . " " . $rec_balance_0 . " " . $rec_balance_1);
                if(intval($balance) != intval($rec_balance_1)){
                    exec('curl -X POST -d "chat_id=-250635592&text=#ERROR BALANCE ' . $account->getId() . "=>" . $balance . "!=" . $rec_balance_1 . '" ' . '"https://api.telegram.org/bot787861588:AAFWCYdIiAoltb0IoM71jlmzq3AHh8kXSMs/sendMessage"');
                }
                $em->persist($wallet);
                $em->flush();
                sleep(20);
            }
        }
    }
}