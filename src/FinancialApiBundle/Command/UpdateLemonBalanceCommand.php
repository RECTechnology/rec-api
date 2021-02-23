<?php

namespace App\FinancialApiBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\FinancialApiBundle\Entity\UserWallet;
use App\FinancialApiBundle\Financial\Currency;

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
        $lemonProvider = $this->getContainer()->get('net.app.in.lemonway.v1');
        $list_balances = $lemonProvider->GetBalances();
        foreach ( $list_balances["WALLETS"]["WALLET"] as $balance ){
            $lemon_id = $balance["ID"];
            $lemon_balance = $balance["BAL"];
            $lemon_status = $balance["S"];
            $output->writeln($lemon_id . "-" . $lemon_balance . "-" . $lemon_status);
            $account = $em->getRepository('FinancialApiBundle:Group')->findOneBy(array(
                'cif' => $lemon_id
            ));
            if($account) {
                $wallet = $account->getWallet('eur');
                $wallet->setBalance(intval($lemon_balance * 100));
                $account->setLwBalance(intval($lemon_balance * 100));
                $em->persist($wallet);
                $em->persist($account);
                $em->flush();
            }
        }
    }
}