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

        $new_limit = new LimitDefinition();
        $new_limit->setCurrency($limit->getCurrency());
        $new_limit->setDay($limit->getDay());
        $new_limit->setWeek($limit->getWeek());
        $new_limit->setMonth($limit->getMonth());
        $new_limit->setYear($limit->getYear());
        $new_limit->setTotal($limit->getTotal());
        $new_limit->setSingle($limit->getSingle());
        $new_limit->setGroup($limit->getGroup());

        switch ($limit->getCname()){
            case 'paynet_reference':
                $new_limit->setCname('paynet_reference-in');
                break;
            case 'halcash_send':
                $new_limit->setCname('halcash_es-out');
                break;
            case 'btc_pay':
                $new_limit->setCname('btc-in');
                break;
            case 'btc_send':
                $new_limit->setCname('btc-out');
                break;
            case 'sepa_in':
                $new_limit->setCname('sepa-in');
                break;
            case 'sepa_out':
                $new_limit->setCname('sepa-out');
                break;
            case 'cryptocapital':
                $new_limit->setCname('cryptocapital-out');
                break;
            case 'fac_pay':
                $new_limit->setCname('fac-in');
                break;
            case 'fac_send':
                $new_limit->setCname('fac-out');
                break;
            case 'pagofacil':
                $new_limit->setCname('pagofacil-in');
                break;
            case 'paynet_payment':
                $new_limit->setCname('paynet_payment-out');
                break;
            case 'sample':
                $new_limit->setCname('sample-in');
                break;
            case 'easypay':
                $new_limit->setCname('easypay-in');
                break;
            case 'bank_transfer':
                $new_limit->setCname('sepa-in');
                break;
            case 'safetypay':
                $new_limit->setCname('safetypay-in');
                break;
            case 'multiva':
                $new_limit->setCname('multiva-in');
                break;
            case 'paysafecard':
                $new_limit->setCname('paysafecard-in');
                break;
            case 'payu':
                $new_limit->setCname('payu-in');
                break;
            case 'toditocash':
                $new_limit->setCname('toditocash-in');
                break;
            case 'pos':
                $new_limit->setCname('pos-in');
                break;
            default:
                $new_limit->setCname($limit->getCname());
                break;
        }

        $em->persist($new_limit);
        $em->flush($new_limit);

    }

    private function _convertFee(ServiceFee $fee){

        $em = $this->getContainer()->get('doctrine')->getManager();

        $new_fee = new ServiceFee();
        $new_fee->setCurrency($fee->getCurrency());
        $new_fee->setGroup($fee->getGroup());
        $new_fee->setFixed($fee->getFixed());
        $new_fee->setVariable($fee->getVariable());

        switch ($fee->getServiceName()){
            case 'paynet_reference':
                $new_fee->setServiceName('paynet_reference-in');
                break;
            case 'halcash_send':
                $new_fee->setServiceName('halcash_es-out');
                break;
            case 'btc_pay':
                $new_fee->setServiceName('btc-in');
                break;
            case 'btc_send':
                $new_fee->setServiceName('btc-out');
                break;
            case 'sepa_in':
                $new_fee->setServiceName('sepa-in');
                break;
            case 'sepa_out':
                $new_fee->setServiceName('sepa-out');
                break;
            case 'cryptocapital':
                $new_fee->setServiceName('cryptocapital-out');
                break;
            case 'fac_pay':
                $new_fee->setServiceName('fac-in');
                break;
            case 'fac_send':
                $new_fee->setServiceName('fac-out');
                break;
            case 'pagofacil':
                $new_fee->setServiceName('pagofacil-in');
                break;
            case 'paynet_payment':
                $new_fee->setServiceName('paynet_payment-out');
                break;
            case 'sample':
                $new_fee->setServiceName('sample-in');
                break;
            case 'easypay':
                $new_fee->setServiceName('easypay-in');
                break;
            case 'bank_transfer':
                $new_fee->setServiceName('sepa-in');
                break;
            case 'safetypay':
                $new_fee->setServiceName('safetypay-in');
                break;
            case 'multiva':
                $new_fee->setServiceName('multiva-in');
                break;
            case 'paysafecard':
                $new_fee->setServiceName('paysafecard-in');
                break;
            case 'payu':
                $new_fee->setServiceName('payu-in');
                break;
            case 'toditocash':
                $new_fee->setServiceName('toditocash-in');
                break;
            case 'pos':
                $new_fee->setServiceName('pos-in');
                break;
            default:
                $new_fee->setServiceName($fee->getServiceName());
                break;
        }

        $em->persist($new_fee);
        $em->flush($new_fee);

    }
}