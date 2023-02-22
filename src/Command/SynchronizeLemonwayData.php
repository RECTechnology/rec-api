<?php
namespace App\Command;

use App\Command\LemonwaySynchronizer\BalancesSynchronizer;
use App\Command\LemonwaySynchronizer\IbanSynchronizer;
use App\Command\LemonwaySynchronizer\KycSynchronizer;
use App\Command\LemonwaySynchronizer\Synchronizer;
use App\Entity\Group;
use App\Financial\Driver\LemonWayInterface;
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
            ->setDescription('Synchronizes data with LemonWay');
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Init command');

        /** @var LemonWayInterface $lw */
        $lw = $this->container->get('net.app.driver.lemonway.eur');

        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine.orm.entity_manager');
        $synchronizers = [
            new BalancesSynchronizer($em, $lw, $output),
            new KycSynchronizer($em, $lw, $output),
            new IbanSynchronizer($em, $lw, $output),
        ];
        /** @var Synchronizer $sync */
        foreach($synchronizers as $sync) $sync->sync();
        $output->writeln('Finish command');
    }
}