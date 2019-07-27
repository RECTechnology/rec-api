<?php
namespace App\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\DependencyInjection\App\Commons\FeeDeal;
use App\FinancialApiBundle\DependencyInjection\App\Commons\LimitAdder;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Exchange;
use App\FinancialApiBundle\Financial\Currency;

class CheckNodeCommand extends SynchronizedContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:node:check')
            ->setDescription('Check node')
        ;
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output){
    }
}