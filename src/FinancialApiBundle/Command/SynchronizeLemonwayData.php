<?php
namespace App\FinancialApiBundle\Command;

use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Financial\Driver\LemonWayInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SynchronizeLemonwayData extends SynchronizedContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('rec:sync:lemonway');
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Init command');

        /** @var LemonWayInterface $lw */
        $lw = $this->getContainer()->get('net.app.driver.lemonway.eur');

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $repo = $em->getRepository(Group::class);
        $accounts = $repo->findBy(['type' => 'COMPANY', 'tier' => 2]);

        $index = [];
        $callParams = ['wallets' => []];
        /** @var Group $account */
        foreach ($accounts as $account){
            $wid = $account->getCif();
            $index[$wid] = $account;
            $callParams['wallets'] []= ['wallet' => $wid];
            $account->setLwBalance(null);
            $em->persist($account);
        }

        $resp = $lw->callService('GetWalletDetailsBatch', $callParams);

        foreach ($resp->wallets as $walletInfo){
            if($walletInfo->WALLET != null){
                $account = $index[$walletInfo->WALLET->ID];
                $account->setLwBalance($walletInfo->WALLET->BAL);
                $em->persist($account);
            }
            else {
                $output->writeln("[WARN] LW error: {$walletInfo->E->Msg}");
            }
        }

        $em->flush();

        $output->writeln('Finish command');
    }
}