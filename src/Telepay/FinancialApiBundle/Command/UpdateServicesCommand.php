<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UpdateServicesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:services:update')
            ->setDescription('Update services.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $em = $this->getContainer()->get('doctrine')->getManager();

        $services = $this->getContainer()->get('net.telepay.service_provider')->findAll();

        $services_cname = array();
        foreach($services as $service){
            $services_cname[] = $service->getCname();
        }

        $limitDefinitionRepo = $em->getRepository('TelepayFinancialApiBundle:LimitDefinition')
            ->findAll();

        $serviceFeeRepo = $em->getRepository('TelepayFinancialApiBundle:ServiceFee')
            ->findAll();

        $limitCountsRepo = $em->getRepository('TelepayFinancialApiBundle:LimitCount')
            ->findAll();

        $deleted_services = array();

        $limitDefinitionCounter = 0;
        foreach($limitDefinitionRepo as $limitDefinition){
            if(in_array($limitDefinition->getCname(),$services_cname) == false){
                $em->remove($limitDefinition);
                $em->flush();
                $deleted_services[] = $limitDefinition->getCname();
                $limitDefinitionCounter++;
            }
        }

        $serviceFeeCounter = 0;
        foreach($serviceFeeRepo as $serviceFee){
            if(in_array($serviceFee->getServiceName(),$services_cname) == false){
                $em->remove($serviceFee);
                $em->flush();
                $deleted_services[] = $serviceFee->getServiceName();
                $serviceFeeCounter++;
            }
        }

        $limitCountsCounter = 0;
        foreach($limitCountsRepo as $limitCounts){
            if(in_array($limitCounts->getCname(),$services_cname) == false){
                $em->remove($limitCounts);
                $em->flush();
                $deleted_services[] = $limitCounts->getCname();
                $limitCountsCounter++;
            }
        }

        $deleted_services = array_unique($deleted_services);

        $deleted_string = implode(",", $deleted_services);

        $output->writeln('Deleted services: '.$deleted_string);
        $output->writeln('LimitDefinitions deleted: '.$limitDefinitionCounter);
        $output->writeln('ServicesFee deleted: '.$serviceFeeCounter);
        $output->writeln('LimitCounts deleted: '.$limitCountsCounter);


    }


}