<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UpdateExchangeCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:exchange:update')
            ->setDescription('Update exchange')
            ->addArgument(
                'currency',
                InputArgument::OPTIONAL,
                'Who do you want to greet?'
            )
            ->addOption(
                'price',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Set new exchange for this currency.',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $query= $this->getContainer()->get('doctrine')->getManager();
        $exchange=$query->getRepository('TelepayFinancialApiBundle:Exchange')->findAll();

        if(!$exchange){
            throw new HttpException(400,'Exchange not found');
        }

        $currency = strtoupper($input->getArgument('currency'));
        $price=$input->getOption('price');
        $price=$price[0];


        switch($currency){
            case 'EUR':
                $exchange[0]->setEur($price);
                break;
            case 'USD':
                $exchange[0]->setUsd($price);
                break;
            case 'MXN':
                $exchange[0]->setMxn($price);
                break;
            case 'BTC':
                $exchange[0]->setBtc($price);
                break;
            case 'FAC':
                $exchange[0]->setFac($price);
                break;
        }


        $query->flush();

        $output->writeln($currency.' exchange Updated');
    }
}