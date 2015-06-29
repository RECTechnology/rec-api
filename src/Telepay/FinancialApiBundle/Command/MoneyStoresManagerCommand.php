<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Telepay\FinancialApiBundle\Financial\MiniumBalanceInterface;
use WebSocket\Client;
use WebSocket\Exception;

class MoneyStoresManagerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:money_store:manager')
            ->setDescription('Guarantees the minimum amount in each registered money storage')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ways = $this->getContainer()->get('net.telepay.ways')->findAll();

        foreach($ways as $way){
            $first = $way->getStartNode();
            $second = $way->getEndNode();

            $output->writeln("First: " . $first->getBalance() . $first->getCurrency() . ", Second: " .$second->getBalance() . $second->getCurrency());

            $minBalance = ($first instanceof MiniumBalanceInterface)?$first->getMiniumBalance():0.0;

            $transactionAmount = $first->getBalance() - $minBalance;
            if($transactionAmount > $way->getMinAmount()){
                $way->send($transactionAmount);
            }
        }
    }
}