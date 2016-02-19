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
use Telepay\FinancialApiBundle\Document\Transaction;

class MigrateTransactionsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:migrate:transactions')
            ->setDescription('Migrate transactions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $transactions = $dm->getRepository("TelepayFinancialApiBundle:Transaction")->findAll();

        foreach($transactions as $transaction){
            $transaction = $this->_convert($transaction);
            $dm->persist($transaction);
            $dm->flush($transaction);
        }

        $output->writeln('All done');
    }

    private function _convert(Transaction $transaction){

        switch ($transaction->getService()){
            case 'paynet_reference':
                $transaction->setMethod('paynet_reference');
                $transaction->setType('in');
                break;
            case 'halcash_send':
                $transaction->setType('out');
                if($transaction->getCurrency() == 'EUR'){
                    $transaction->setMethod('halcash_es');
                }else{
                    $transaction->setMethod('halcash_pl');
                }
                break;
            case 'btc_pay':
                $transaction->setMethod('btc');
                $transaction->setType('in');
                break;
            case 'btc_send':
                $transaction->setMethod('btc');
                $transaction->setType('out');
                break;
            case 'sepa_in':
                $transaction->setMethod('sepa');
                $transaction->setType('in');
                break;
            case 'sepa_out':
                $transaction->setMethod('sepa');
                $transaction->setType('out');
                break;
            case 'cryptocapital':
                $transaction->setMethod('cryptocapital');
                $transaction->setType('out');
                break;
            case 'fac_pay':
                $transaction->setMethod('fac');
                $transaction->setType('in');
                break;
            case 'fac_send':
                $transaction->setMethod('fac');
                $transaction->setType('out');
                break;
            case 'pagofacil':
                $transaction->setMethod('pagofacil');
                $transaction->setType('in');
                break;
            case 'paynet_payment':
                $transaction->setMethod('paynet_payment');
                $transaction->setType('out');
                break;
            case 'sample':
                $transaction->setMethod('sample');
                $transaction->setType('in');
                break;
            case 'easypay':
                $transaction->setMethod('easypay');
                $transaction->setType('in');
                break;
            case 'bank_transfer':
                $transaction->setMethod('sepa');
                $transaction->setType('in');
                break;
            case 'safetypay':
                $transaction->setMethod('safetypay');
                $transaction->setType('in');
                break;
            case 'multiva':
                $transaction->setMethod('multiva');
                $transaction->setType('in');
                break;
            case 'paysafecard':
                $transaction->setMethod('paysafecard');
                $transaction->setType('in');
                break;
            case 'payu':
                $transaction->setMethod('payu');
                $transaction->setType('in');
                break;
            case 'toditocash':
                $transaction->setMethod('toditocash');
                $transaction->setType('in');
                break;
            case 'pos':
                $transaction->setMethod('pos');
                $transaction->setType('in');
                break;
            default:
                break;
        }


        return $transaction;

    }
}