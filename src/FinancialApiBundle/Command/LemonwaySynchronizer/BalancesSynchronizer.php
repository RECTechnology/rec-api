<?php


namespace App\FinancialApiBundle\Command\LemonwaySynchronizer;


use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Financial\Driver\LemonWayInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BalancesSynchronizer extends AbstractSynchronizer {

    function sync(){

        $index = $this->getWalletsIndexedByLemonId();

        $callParams = ['wallets' => []];
        foreach ($index as $wid => $accounts){
            $callParams['wallets'] []= ['wallet' => $wid];
        }

        $resp = $this->lw->callService('GetWalletDetailsBatch', $callParams);

        foreach ($resp->wallets as $walletInfo){
            if($walletInfo->WALLET != null){
                $this->output->writeln("[INFO] Found LW ID {$walletInfo->WALLET->ID}");
                $accounts = $index[strtoupper($walletInfo->WALLET->ID)];
                foreach ($accounts as $account) {
                    $account->setLwBalance(intval($walletInfo->WALLET->BAL * 100.0));
                    $this->em->persist($account);
                }
            }
            else {
                $this->output->writeln("[WARN] LW error: {$walletInfo->E->Msg}");
            }
        }

        $this->em->flush();

    }

}