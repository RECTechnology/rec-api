<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AddLinkingCodePOSCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:pos:linking_code')
            ->setDescription('Generate Linking Code for each POS.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $pos_list = $em->getRepository('TelepayFinancialApiBundle:POS')->findAll();

        $output->writeln('Generating '.count($pos_list).' codes');
        foreach ($pos_list as $pos){
            $code = $pos->generateCode(6);
            $pos->setLinkingCode($code);
            $output->writeln($code);
            $em->flush();
        }

        $output->writeln('FINISH');
    }

}