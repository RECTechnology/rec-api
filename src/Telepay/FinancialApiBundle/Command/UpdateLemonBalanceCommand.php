<?php

namespace Telepay\FinancialApiBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

class UpdateLemonBalanceCommand extends ContainerAwareCommand{
    protected function configure()
    {
        $this
            ->setName('rec:lemon:check:balance')
            ->setDescription('Check lemon balances')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $em = $this->getContainer()->get('doctrine')->getManager();
        $lemonProvider = $this->getContainer()->get('net.telepay.in.lemonway.v1');
        $list_balances = $lemonProvider->GetBalances();

        foreach ( $list_balances["WALLETS"] as $balance ){
            $lemon_id = $balance->ID;
            $lemon_balance = $balance->BAL;
            $lemon_status = $balance->S;
            $output->writeln($lemon_id . "-" . $lemon_balance . "-" . $lemon_status);
            //$em->
            //$wallet = $account->getWallet('rec');
            //$address = $account->getRecAddress();
            //$balance = $wallet->getBalance();
            //$em->persist($wallet);
            //$em->flush();
        }
    }
}