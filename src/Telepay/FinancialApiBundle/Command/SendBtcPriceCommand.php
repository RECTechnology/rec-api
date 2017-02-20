<?php
namespace Telepay\FinancialApiBundle\Command;

use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Telepay\FinancialApiBundle\Entity\Exchange;

class SendBtcPriceCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:exchange:price:send')
            ->setDescription('Send all exchange prices')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $exchangeRepo = $em->getRepository("TelepayFinancialApiBundle:Exchange");
        $exchanges = $exchangeRepo->findBy(
            array(
                'src'=>'BTC',
                'dst'=>'EUR')
        );
        $output->writeln("Date,Price");
        foreach ($exchanges as $exchange) {
            $date = $exchange->getDate();
            $day = $date->format('d');
            $month = $date->format('m');
            $year = $date->format('Y');
            $ex_date = $date->format('Y-m-d H:i:s');
            if($month == '1' && $year == '2017') {
                $output->writeln($ex_date . "," . ($exchange->getPrice() * 100000000));
            }
        }
    }
}