<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DeleteOldFeesLimitsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:groups:fees-limits:delete-old')
            ->setDescription('Update fees and limits by actives services.')
            ->addArgument(
                'old-method',
                InputArgument::OPTIONAL,
                'What old method we have to remove?'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $method = $input->getArgument('old-method');
        $em = $this->getContainer()->get('doctrine')->getManager();

        $limits = $em->getRepository('TelepayFinancialApiBundle:LimitDefinition')->findBy(
            array(
                'cname' =>  $method
            )
        );

        $fees = $em->getRepository('TelepayFinancialApiBundle:ServiceFee')->findBy(
            array(
                'service_name' =>  $method
            )
        );

        $removedLimits = 0;
        foreach($limits as $limit){
            $em->remove($limit);
            $em->flush();
            $removedLimits++;
        }

        $removedFees = 0;
        foreach($fees as $fee){
            $em->remove($fee);
            $em->flush();
            $removedFees++;
        }

        $output->writeln('Removed limits: '.$removedLimits);
        $output->writeln('Removed fees: '.$removedFees);

    }


}