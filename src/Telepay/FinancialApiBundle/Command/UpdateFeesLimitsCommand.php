<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UpdateFeesLimitsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:fees-limits:update')
            ->setDescription('Update fee and limits.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $em = $this->getContainer()->get('doctrine')->getManager();

        $services = $this->getContainer()->get('net.telepay.service_provider')->findAll();

        $limitDefinitionRepo = $em->getRepository('TelepayFinancialApiBundle:LimitDefinition');
        $limitDefinitionCounter = 0;
        $serviceFeeRepo = $em->getRepository('TelepayFinancialApiBundle:ServiceFee');
        $serviceFeeCounter = 0;

        foreach($services as $service){
            $limitDefinitions = $limitDefinitionRepo->findBy(array(
                'cname'  =>  $service->getCname()
            ));

            foreach($limitDefinitions as $limitDefinition){
                $limitDefinition->setCurrency($service->getCurrency());
                $em->persist($limitDefinition);
                $limitDefinitionCounter ++;
            }

            $serviceFees = $serviceFeeRepo->findBy(array(
                'service_name' =>  $service->getCname()
            ));

            foreach($serviceFees as $serviceFee){
                $serviceFee->setCurrency($service->getCurrency());
                $em->persist($serviceFee);
                $serviceFeeCounter ++;
            }

            $em->flush();
        }

        $output->writeln('Updated limits: '.$limitDefinitionCounter);
        $output->writeln('ServicesFee updated: '.$serviceFeeCounter);

    }


}