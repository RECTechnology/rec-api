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
                'Daily("d") or monthly("m") (monthly by default).',
                null
            )
            ->addOption(
                'type',
                null,
                InputOption::VALUE_REQUIRED,
                'Buy, sell, all (all by default).',
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
        if(intval($this_month) == 1) $this_year = intval($this_year)-1;

        $type = 'all';
        if($input->getOption('type')){
            if($input->getOption('type') == 'buy') $type = 'buy';
            if($input->getOption('type') == 'sell') $type = 'sell';
        }

        if($type == 'sell' || $type == 'all') {
            $qb = $this->getContainer()->get('doctrine')->getRepository('TelepayFinancialApiBundle:Exchange')->createQueryBuilder('w');
            $qb->Select("w.date as date, w.price as price")
                ->where("w.src = '" . $this->currency . "' and w.dst = 'EUR' and YEAR(date) = '" . $this_year . "' and MONTH(date) = '" . $this_month . "'  and w.id > 15000000");
            $query = $qb->getQuery()->getResult();

            foreach($query as $exchange){
                $date = $exchange['date'];
                $minute = $date->format('i');
                $hour = $date->format('H');
                $day = $date->format('d');
                $month = $date->format('m');
                $year = $date->format('Y');
                $ex_date = $date->format('Y-m-d H:i:s');
                $list_values[$year. $month . $day . $hour . $minute]=array(
                    'ex_date' => $ex_date,
                    'sell' => $exchange['price'] * 100000000
                );
            }
        }

        if($type == 'buy' || $type == 'all') {
            $qb->Select("w.date as date, w.price as price")
                ->where("w.src = 'EUR' and w.dst = '" . $this->currency . "' and YEAR(date) = '" . $this_year . "' and MONTH(date) = '" . $this_month . "'  and w.id > 15000000");
        }

        if($type == 'all'){
            $output->writeln("Date,Buy Price, Sell Price");
        }
        else{
            $output->writeln("Date,Price");
        }

        $list_values = array();

        if($type == 'buy'){
            $output->writeln($ex_date . "," . 100000000/($exchange['price']));
        }else{
            $output->writeln($ex_date . "," . ($exchange['price'] * 100000000));
        }
    }
}