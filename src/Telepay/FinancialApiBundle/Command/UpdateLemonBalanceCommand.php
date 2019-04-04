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
        $output->writeln(json_encode($list_balances));

        foreach ( $list_balances["WALLETS"] as $balance ){
            $output->writeln(json_encode($balance));
            $lemon_id = $balance["WALLET"]["ID"];
            $lemon_balance = $balance["BAL"];
            $lemon_status = $balance["WALLET"]["S"];
            $output->writeln($lemon_id . "-" . $lemon_balance . "-" . $lemon_status);
            //$em->
            //$wallet = $account->getWallet('eur');
            //$wallet->setBalance(intval($lemon_balance*100));
            //$em->persist($wallet);
            //$em->flush();
        }
    }
}