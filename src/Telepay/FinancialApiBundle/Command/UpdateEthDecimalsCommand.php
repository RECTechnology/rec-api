<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Telepay\FinancialApiBundle\Document\Transaction;

class UpdateEthDecimalsCommand extends ContainerAwareCommand{
    protected function configure(){
        $this
            ->setName('telepay:eth:update')
            ->setDescription('Update decimals in eth transactions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $ICO_address = "0x";
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        //update swift
        $swifts = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('method_out')->in(array("eth"))
            ->field('type')->in(array("swift"))
            ->getQuery();

        $output->writeln('Found '.count($swifts).' swift transactions');
        $swiftCounter = 0;
        foreach ($swifts->toArray() as $swift){
            $pay_out_info = $swift->getPayOutInfo();
            $pay_out_info['address'] = $ICO_address;

            $dataIn = $swift->getDataIn();
            $dataIn['address'] = $ICO_address;

            $swift->setPayOutInfo($pay_out_info);

            $dm->flush();
            $swiftCounter++;

        }
        $output->writeln('Updated '.$swiftCounter.' swifts');
        $output->writeln('END');
    }
}