<?php
namespace App\FinancialApiBundle\Command;

use App\FinancialApiBundle\Command\LemonwaySynchronizer\BalancesSynchronizer;
use App\FinancialApiBundle\Command\LemonwaySynchronizer\KycSynchronizer;
use App\FinancialApiBundle\Command\LemonwaySynchronizer\Synchronizer;
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
        $this->setName('rec:sync:lemonway')
            ->setDescription('Synchronizes data with LemonWay')
            ->addOption(
                'balances',
                null,
                InputOption::VALUE_OPTIONAL,
                'Synchronize lemonway balances',
                null
            )
            ->addOption(
                'kyc',
                null,
                InputOption::VALUE_OPTIONAL,
                'Synchronize lemonway documents',
                null
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_OPTIONAL,
                'Synchronize everything',
                null
            );
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Init command');

        /** @var LemonWayInterface $lw */
        $lw = $this->getContainer()->get('net.app.driver.lemonway.eur');

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $synchronizers = [];
        if($input->getOption('balances') || $input->getOption('all')){
            $synchronizers []= new BalancesSynchronizer($em, $lw, $output);
        }
        if($input->getOption('kyc') || $input->getOption('all')){
            $synchronizers []= new KycSynchronizer($em, $lw, $output);
        }
        /** @var Synchronizer $sync */
        foreach($synchronizers as $sync) $sync->sync();
        $output->writeln('Finish command');
    }
}