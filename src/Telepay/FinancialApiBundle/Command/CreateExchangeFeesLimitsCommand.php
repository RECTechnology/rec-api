<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Entity\Exchange;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\ServiceFee;

class CreateExchangeFeesLimitsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:exchange:fees-limits:create')
            ->setDescription('Create fees and limits for exchange')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $exchanges = $this->getContainer()->get('net.telepay.exchange_provider')->findAll();

        $groups = $em->getRepository("TelepayFinancialApiBundle:Group")->findAll();

        $createdLimits = 0;
        $createdFees = 0;

        foreach($exchanges as $exchange){
            //create limit foreach group
            //create fee foreach group
            foreach($groups as $group){
                $limit = new LimitDefinition();
                $limit->setDay(0);
                $limit->setWeek(0);
                $limit->setMonth(0);
                $limit->setYear(0);
                $limit->setTotal(0);
                $limit->setSingle(0);
                $limit->setCname('exchange_'.$exchange->getCname());
                $limit->setCurrency($exchange->getCurrencyOut());
                $limit->setGroup($group);

                $fee = new ServiceFee();
                $fee->setFixed(0);
                $fee->setVariable(0);
                $fee->setCurrency($exchange->getCurrencyOut());
                $fee->setServiceName('exchange_'.$exchange->getCname());
                $fee->setGroup($group);

                $em->persist($limit);
                $em->persist($fee);
                $em->flush();

                $createdFees ++;
                $createdLimits ++;
            }
        }

        $output->writeln('Created Limits: '.$createdLimits);
        $output->writeln('Created Fees: '.$createdFees);
    }
}