<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/15/14
 * Time: 1:27 PM
 */

namespace Telepay\FinancialApiBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateExchangeCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:migrate:exchange-info')
            ->setDescription('Migrate exchange info')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $transactions = $dm->getRepository("TelepayFinancialApiBundle:Transaction")->findAll();

        $output->writeln('Migrating '.count($transactions).' transactions...');
        $counterTransactions = 0;
        foreach($transactions as $transaction){
            //TODO filter by exchanges
            $service = $transaction->getService();
            $method = $transaction->getMethod();

            $patron = '/^exchange_/';
            if(preg_match($patron, $service) || preg_match($patron, $method)){
                $output->writeln($service);
                $explodeTypes = explode('_',$service);
                $currencies = explode('to', $explodeTypes[1]);
                $currency_in = $currencies[0];
                $currency_out = $currencies[1];
                if($transaction->getType() == 'in'){
                    //información del dinero que entra a tu cuenta
                    $pay_in_info = array(
                        'amount'    =>  $transaction->getAmount(),
                        'currency'  =>  $transaction->getCurrency(),
                        'scale'     =>  $transaction->getScale(),
                        'concept'   =>  'Exchange '.$currency_in.' to '.$currency_out,
                        'price'     =>  'undefined'

                    );
                    $transaction->setPayInInfo($pay_in_info);
                }else{
                    $pay_out_info = array(
                        'amount'    =>  $transaction->getAmount(),
                        'currency'  =>  $transaction->getCurrency(),
                        'scale'     =>  $transaction->getScale(),
                        'concept'   =>  'Exchange '.$currency_in.' to '.$currency_out,
                        'price'     =>  'undefined'
                    );
                    $transaction->setPayOutInfo($pay_out_info);
                }
                $dm->persist($transaction);
                $dm->flush($transaction);

                $counterTransactions ++;
            }


        }

        $output->writeln($counterTransactions.' transactions updated');

        $output->writeln('All done');
    }

}