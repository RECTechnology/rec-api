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
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\ServiceFee;

class MigrateLimitsFeesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:migrate:limits:fees')
            ->setDescription('Migrate limits and fees')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $limits = $em->getRepository("TelepayFinancialApiBundle:LimitDefinition")->findAll();
        $fees = $em->getRepository("TelepayFinancialApiBundle:ServiceFee")->findAll();

        $output->writeln('Migrating '.count($limits).' limits and '.count($fees).' fees');
        $output->writeln('Migrating Limits...');

        $countLimits = 0;
        $countFees = 0;
        foreach($limits as $limit){
            $this->_convertLimit($limit);
            $countLimits ++;

        }

        $output->writeln($countLimits.' limits migrated');

        $output->writeln('Migrating Fees...');

        foreach($fees as $fee){
            $this->_convertFee($fee);
            $countFees++;

        }
        $output->writeln($countFees.' fees migrated');

        $output->writeln('All done');
    }

    private function _convertLimit(LimitDefinition $limit){

        $em = $this->getContainer()->get('doctrine')->getManager();

        switch ($limit->getCname()){
            case 'paynet_reference':
                $limit->setCname('paynet_reference-in');
                break;
            case 'halcash_send':
                $limit->setCname('halcash_es-out');
                break;
            case 'btc_pay':
                $limit->setCname('btc-in');
                break;
            case 'btc_send':
                $limit->setCname('btc-out');
                break;
            case 'sepa_in':
                $limit->setCname('sepa-in');
                break;
            case 'sepa_out':
                $limit->setCname('sepa-out');
                break;
            case 'cryptocapital':
                $limit->setCname('cryptocapital-out');
                break;
            case 'fac_pay':
                $limit->setCname('fac-in');
                break;
            case 'fac_send':
                $limit->setCname('fac-out');
                break;
            case 'pagofacil':
                $limit->setCname('pagofacil-in');
                break;
            case 'paynet_payment':
                $limit->setCname('paynet_payment-out');
                break;
            case 'sample':
                $limit->setCname('sample-in');
                break;
            case 'easypay':
                $limit->setCname('easypay-in');
                break;
            case 'bank_transfer':
                $limit->setCname('sepa-in');
                break;
            case 'safetypay':
                $limit->setCname('safetypay-in');
                break;
            case 'multiva':
                $limit->setCname('multiva-in');
                break;
            case 'paysafecard':
                $limit->setCname('paysafecard-in');
                break;
            case 'payu':
                $limit->setCname('payu-in');
                break;
            case 'toditocash':
                $limit->setCname('toditocash-in');
                break;
            case 'pos':
                $limit->setCname('pos-in');
                break;
            default:
                $limit->setCname($limit->getCname());
                break;
        }

        $em->persist($limit);
        $em->flush($limit);

    }

    private function _convertFee(ServiceFee $fee){

        $em = $this->getContainer()->get('doctrine')->getManager();

        switch ($fee->getServiceName()){
            case 'paynet_reference':
                $fee->setServiceName('paynet_reference-in');
                break;
            case 'halcash_send':
                $fee->setServiceName('halcash_es-out');
                break;
            case 'btc_pay':
                $fee->setServiceName('btc-in');
                break;
            case 'btc_send':
                $fee->setServiceName('btc-out');
                break;
            case 'sepa_in':
                $fee->setServiceName('sepa-in');
                break;
            case 'sepa_out':
                $fee->setServiceName('sepa-out');
                break;
            case 'cryptocapital':
                $fee->setServiceName('cryptocapital-out');
                break;
            case 'fac_pay':
                $fee->setServiceName('fac-in');
                break;
            case 'fac_send':
                $fee->setServiceName('fac-out');
                break;
            case 'pagofacil':
                $fee->setServiceName('pagofacil-in');
                break;
            case 'paynet_payment':
                $fee->setServiceName('paynet_payment-out');
                break;
            case 'sample':
                $fee->setServiceName('sample-in');
                break;
            case 'easypay':
                $fee->setServiceName('easypay-in');
                break;
            case 'bank_transfer':
                $fee->setServiceName('sepa-in');
                break;
            case 'safetypay':
                $fee->setServiceName('safetypay-in');
                break;
            case 'multiva':
                $fee->setServiceName('multiva-in');
                break;
            case 'paysafecard':
                $fee->setServiceName('paysafecard-in');
                break;
            case 'payu':
                $fee->setServiceName('payu-in');
                break;
            case 'toditocash':
                $fee->setServiceName('toditocash-in');
                break;
            case 'pos':
                $fee->setServiceName('pos-in');
                break;
            default:
                $fee->setServiceName($fee->getServiceName());
                break;
        }

        $em->persist($fee);
        $em->flush($fee);

    }
}