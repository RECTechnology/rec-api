<?php
namespace App\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\FinancialApiBundle\Entity\Exchange;

class CreateExchangeCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:exchange:create')
            ->setDescription('Create exchange')
            ->addOption(
                'src',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Set source currency.',
                null
            )
            ->addOption(
                'dst',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Set destiny currency.',
                null
            )
            ->addOption(
                'price',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Set price.',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $src = $input->getOption('src');
        $dst = $input->getOption('dst');
        $price = $input->getOption('price');

        $exchange = new Exchange();
        $exchange->setSrc($src[0]);
        $exchange->setDst($dst[0]);
        $exchange->setPrice($price[0]);
        $date = new \DateTime();
        $exchange->setDate($date);

        $em->persist($exchange);

        $em->flush();

        $output->writeln('New exchange Updated');
    }
}