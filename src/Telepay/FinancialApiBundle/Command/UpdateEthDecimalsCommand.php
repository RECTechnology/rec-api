<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Telepay\FinancialApiBundle\Document\Transaction;

class UpdateEthDecimalsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:eth:update')
            ->setDescription('Update decimals in eth transactions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();

        //update wallets
        $wallets = $em->getRepository('TelepayFinancialApiBundle:UserWallet')->findBy(array(
            'currency'  =>  'eth'
        ));

        $walletCounter = 0;
        $output->writeln('Found '.count($wallets). ' wallets');
        foreach ($wallets as $wallet){
            $wallet->setAvailable($wallet->getAvailable() / pow(10,10));
            $wallet->setBalance($wallet->getBalance() / pow(10,10));

            $em->flush();
            $walletCounter++;
        }

        $output->writeln('Updated '.$walletCounter.' wallets');
        //update eth-in
        $methodsIn = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('type')->in(array('in'))
            ->field('method')->in(array('eth'))
            ->getQuery();

        $methodsCounter = 0;
        $output->writeln('Found '.count($methodsIn).' eth-in transactions');
        foreach ($methodsIn->toArray() as $methodIn){
            $methodIn->setAmount($methodIn->getAmount() / pow(10,10));
            $pay_in_info = $methodIn->getPayInInfo();
            $pay_in_info['amount'] = $pay_in_info['amount'] / pow(10,10);
            $pay_in_info['received'] = $pay_in_info['received'] / pow(10,10);
            $pay_in_info['scale'] = 8;

            $methodIn->setScale(8);
            $methodIn->setTotal($methodIn->getTotal() / pow(10,10));
            $methodIn->setPayInInfo($pay_in_info);

            $dm->flush();

            $methodsCounter++;

        }

        $output->writeln('Updated '.$methodsCounter.' eth-in transactions');

        //update swift
        $swifts = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('method_out')->in(array("eth"))
            ->field('type')->in(array("swift"))
            ->getQuery();

        $output->writeln('Found '.count($swifts).' swift transactions');
        $swiftCounter = 0;
        foreach ($swifts->toArray() as $swift){
            $pay_out_info = $swift->getPayOutInfo();
            $pay_out_info['amount'] = $pay_out_info['amount'] / pow(10,10);
            $pay_out_info['scale'] = 8;

            $dataIn = $swift->getDataIn();
            $dataIn['amount'] = $dataIn['amount'] / pow(10,10);
            $dataIn['scale'] = $dataIn['scale'] / pow(10,10);

            $swift->setPayOutInfo($pay_out_info);
            $swift->setAmount($swift->getAmount() / pow(10,10));
            $swift->setTotal($swift->getTotal() / pow(10,10));
            $swift->setScale(8);
            $swift->setPrice($swift->getPrice() / pow(10,10));

            $dm->flush();
            $swiftCounter++;

        }

        $output->writeln('Updated '.$swiftCounter.' swifts');

        //find all deposits eth and change scale
        $tokens = $em->getRepository('TelepayFinancialApiBundle:CashInTokens')->findBy(array(
            'currency'  =>  'eth'
        ));

        $output->writeln('Found '.count($tokens).' tokens');

        $depositCounter = 0;
        foreach ($tokens as $token){
            //TODO search deposits
            $deposits = $token->getDeposits();
            foreach ($deposits as $deposit){
                $deposit->setAmount($deposit->getAmount() / pow(10,10));

                $em->flush();

                $depositCounter++;
            }
        }

        $output->writeln('Updated '.$depositCounter.' deposits');

        $output->writeln('END');
    }
}