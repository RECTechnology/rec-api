<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Telepay\FinancialApiBundle\Entity\TierLimit;
use Telepay\FinancialApiBundle\Financial\Currency;

class CreateTierLimitsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:tier-limits:create')
            ->setDescription('Create Tier limits.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $em = $this->getContainer()->get('doctrine')->getManager();

        $methods = $this->getContainer()->get('net.telepay.method_provider')->findAll();


        foreach($methods as $method){
            $tier0 = new TierLimit();
            $tier0->createDefault(0, $method->getCname().'-'.$method->getType(), $method->getCurrency());

            $tier1 = new TierLimit();
            $tier1->createDefault(1, $method->getCname().'-'.$method->getType(), $method->getCurrency());

            $tier2 = new TierLimit();
            $tier2->createDefault(2, $method->getCname().'-'.$method->getType(), $method->getCurrency());

            $tier3 = new TierLimit();
            $tier3->createDefault(3, $method->getCname().'-'.$method->getType(), $method->getCurrency());

            $em->persist($tier0);
            $em->persist($tier1);
            $em->persist($tier2);
            $em->persist($tier3);

            $em->flush();

        }

        $currencies = Currency::$ALL;
        foreach($currencies as $currency){

            $tier0 = new TierLimit();
            $tier0->createDefault(0, 'exchange_'.$currency, $currency);

            $tier1 = new TierLimit();
            $tier1->createDefault(1, 'exchange_'.$currency, $currency);

            $tier2 = new TierLimit();
            $tier2->createDefault(2, 'exchange_'.$currency, $currency);

            $tier3 = new TierLimit();
            $tier3->createDefault(3, 'exchange_'.$currency, $currency);

            $em->persist($tier0);
            $em->persist($tier1);
            $em->persist($tier2);
            $em->persist($tier3);

            $em->flush();

        }

//        $exchanges = $this->getContainer()->get('net.telepay.exchange_provider')->findAll();
//
//        foreach($exchanges as $exchange){
//
//            $tier0 = new TierLimit();
//            $tier0->createDefault(0, 'exchange_'.$exchange->getCname(), $exchange->getCurrencyOut());
//
//            $tier1 = new TierLimit();
//            $tier1->createDefault(1, 'exchange_'.$exchange->getCname(), $exchange->getCurrencyOut());
//
//            $tier2 = new TierLimit();
//            $tier2->createDefault(2, 'exchange_'.$exchange->getCname(), $exchange->getCurrencyOut());
//
//            $tier3 = new TierLimit();
//            $tier3->createDefault(3, 'exchange_'.$exchange->getCname(), $exchange->getCurrencyOut());
//
//            $em->persist($tier0);
//            $em->persist($tier1);
//            $em->persist($tier2);
//            $em->persist($tier3);
//
//            $em->flush();
//
//        }

    }

}