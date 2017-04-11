<?php
namespace Telepay\FinancialApiBundle\Command;

use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Telepay\FinancialApiBundle\Entity\Exchange;

class SendBtcPriceCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:exchange:price:send')
            ->setDescription('Send all exchange prices')
            ->addOption(
                'currency',
                null,
                InputOption::VALUE_REQUIRED,
                'Define the currency to do all the prices (BTC by default).',
                null
            )
            ->addOption(
                'mode',
                null,
                InputOption::VALUE_REQUIRED,
                'Daily or monthly (daily by default).',
                null
            )
        ;
    }

    public $currency;

    protected function execute(InputInterface $input, OutputInterface $output) {
        if($input->getOption('currency')){
            $this->currency = strtoupper($input->getOption('currency'));
        }
        else{
            $this->currency = 'BTC';
        }

        $today = date('d');
        $this_month = date('m');
        $this_year = date('Y');

        $qb = $this->getContainer()->get('doctrine')->getRepository('TelepayFinancialApiBundle:Exchange')->createQueryBuilder('w');
        $qb->Select("w.date as date, w.price as price")
            ->where("w.src = '" . $this->currency . "' and w.dst = 'EUR' and w.id > 6000001");
        $query = $qb->getQuery()->getResult();

        $output->writeln("Date,Price");
        foreach($query as $exchange){
            $date = $exchange['date'];
            $day = $date->format('d');
            $month = $date->format('m');
            $year = $date->format('Y');
            $ex_date = $date->format('Y-m-d H:i:s');
            if($month == $this_month-1 && $year == $this_year) {
                $output->writeln($ex_date . "," . ($exchange['price'] * 100000000));
            }
        }
    }
}