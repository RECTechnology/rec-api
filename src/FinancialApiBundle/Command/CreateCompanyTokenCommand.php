<?php
namespace App\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCompanyTokenCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:company:create:tokens')
            ->setDescription('Create tokens foreach company')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $em = $this->getContainer()->get('doctrine')->getManager();

        $companies = $em->getRepository('FinancialApiBundle:Group')->findAll();

        $output->writeln('INIT creating tokens for '.count($companies));

        foreach($companies as $company){
            $company->setCompanyToken(uniqid());
            $em->flush();
        }

        $output->writeln('FINISH');

    }

}