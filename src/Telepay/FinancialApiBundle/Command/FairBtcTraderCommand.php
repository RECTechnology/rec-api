<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FairBtcTraderCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:trader:fair-btc')
            ->setDescription('Trades with FairCoin -> Bitcoin')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fairBtcTrader = $this->getContainer()->get('net.telepay.trader.FACxBTC');

        //$fairBtcTrader->sell(2146.03787368);
        $output->writeln(print_r($fairBtcTrader->withdraw(), true));

        $output->writeln("Sold");


    }
}