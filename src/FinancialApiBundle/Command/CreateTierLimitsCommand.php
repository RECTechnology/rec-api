<?php
namespace App\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\FinancialApiBundle\Entity\TierLimit;
use App\FinancialApiBundle\Financial\Currency;

class CreateTierLimitsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:tier-limits:create')
            ->setDescription('Create Tier limits.')
            ->addOption(
                'currency',
                null,
                InputOption::VALUE_REQUIRED ,
                'Set source currency.',
                null
            )
            ->addOption(
                'method',
                null,
                InputOption::VALUE_REQUIRED,
                'Set method name like eth-in.',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $em = $this->getContainer()->get('doctrine')->getManager();


        $method = $input->getOption('method');
        $currency = $input->getOption('currency');

        for($i = 0; $i <= 4; $i++){
            $this->_createTier($method.'-out', $currency, $i);
            $this->_createTier($method.'-in', $currency, $i);
            $this->_createTier('exchange_'.$currency, $currency, $i);
        }

    }

    private function _createTier($method, $currency, $tier){

        $em = $this->getContainer()->get('doctrine')->getManager();

        //TODO search tier limits before create them.
        $tierLimit = $em->getRepository('FinancialApiBundle:TierLimit')->findOneBy(array(
            'method'    =>  $method,
            'tier'  =>  $tier
        ));

        if(!$tierLimit){
            $tierLimit = new TierLimit();
            $tierLimit->createDefault($tier, $method, strtoupper($currency));

            $em->persist($tierLimit);
            $em->flush();
        }

    }

}