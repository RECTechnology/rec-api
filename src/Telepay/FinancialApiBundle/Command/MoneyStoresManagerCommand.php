<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Telepay\FinancialApiBundle\Financial\MiniumBalanceInterface;
use Telepay\FinancialApiBundle\Financial\Currency;
use WebSocket\Client;
use WebSocket\Exception;

class MoneyStoresManagerCommand extends ContainerAwareCommand{
    protected function configure(){
        $this
            ->setName('telepay:money_store:manager')
            ->setDescription('Guarantees the minimum amount in each registered money storage')
            ->addOption(
                'currency',
                null,
                InputOption::VALUE_REQUIRED,
                'Define the currency to do all the calculations.',
                null
            )
        ;
    }

    public $default_currency;

    protected function execute(InputInterface $input, OutputInterface $output){
        if($input->getOption('currency')){
            $this->default_currency = strtoupper($input->getOption('currency'));
        }
        else{
            $this->default_currency = 'EUR';
        }

        $em = $this->getContainer()->get('doctrine')->getManager();
        $groupRepo = $em->getRepository('TelepayFinancialApiBundle:Group');
        $groups = $groupRepo->findBy(
            array('own'=>true)
        );
        $chipchap_groups = array();
        foreach($groups as $group){
            $chipchap_groups[] = $group->getId();
        }

        $qb = $this->getContainer()->get('doctrine')->getRepository('TelepayFinancialApiBundle:UserWallet')->createQueryBuilder('w');
        $qb->Select('SUM(w.available) as available, SUM(w.balance) as balance, w.currency')
            ->where('w.group NOT IN (:groups)')
            ->setParameter('groups', $chipchap_groups)
            ->groupBy('w.currency');

        $query = $qb->getQuery()->getResult();

        $balances = [];
        foreach($query as $balance){
            $balance['available'] = round($balance['available'],0);
            $balance['balance'] = round($balance['balance'],0);
            $balance['scale'] = Currency::$SCALE[$balance['currency']];
            $balances[$balance['currency']] = $balance;
        }

        $system_data = array();
        $system_data['wallets'] = array();
        $system_data['transfers'] = array();

        $em = $this->getContainer()->get('doctrine')->getManager();
        $walletRepo = $em->getRepository("TelepayFinancialApiBundle:WalletConf");
        $wallets = $walletRepo->findAll();
        foreach ($wallets as $wallet) {
            $type = $wallet->getType();
            $currency = $wallet->getCurrency();
            $name = $type . '_' . $currency;
            $system_data['wallets'][$name]['type']=$type;
            $system_data['wallets'][$name]['currency']=$currency;
            $system_data['wallets'][$name]['priority']=$wallet->getPriority();
            $wallet_conf = $this->getContainer()->get('net.telepay.wallet.' . $type . '.' . $currency);
            $currency = strtoupper($currency);
            $balance = $balances[$currency]['balance'];
            $min = round($wallet->getMinBalance() * $balance / 100 + $wallet->getFixedAmount(),0);
            $max = round($wallet->getMaxBalance() * $balance / 100 + $wallet->getFixedAmount(),0);
            $perfect = round($wallet->getPerfectBalance() * $balance / 100 + $wallet->getFixedAmount(),0);
            $now = round($wallet_conf->getFakeBalance() * (pow(10, Currency::$SCALE[$currency])), 0);
            $receiving_data = $this->receiving($name);
            $receiving = $receiving_data['amount'];
            $system_data['transfers'] = array_merge($system_data['transfers'], $receiving_data['list']);
            $system_data['wallets'][$name]['now']=$now;
            $system_data['wallets'][$name]['receiving']=$receiving;

            $output->writeln("Now: " . $now/(pow(10, Currency::$SCALE[$currency])) . " " . $currency);
            if($now + $receiving < $min){
                $need = $perfect - $now - $receiving;
                $output->writeln("Need: " . $need/(pow(10, Currency::$SCALE[$currency])) . " " . $currency);
                $output->writeln("Need: " . $this->_exchange($need, $currency, $this->default_currency)/(pow(10, Currency::$SCALE[$this->default_currency])) . " " . $this->default_currency);
                $output->writeln("Need: " . round($this->_exchange($need, $currency, $this->default_currency),0) . " " . $this->default_currency . " cents");
                $system_data['wallets'][$name]['need']=$need;
                $system_data['wallets'][$name]['need_default']=round($this->_exchange($need, $currency, $this->default_currency),0);
            }
            elseif($now + $receiving > $max){
                $excess = $now + $receiving - $perfect;
                $output->writeln("Sobre: " . $excess/(pow(10, Currency::$SCALE[$currency])) . " " . $currency);
                $output->writeln("Sobre: " . $this->_exchange($excess, $currency, $this->default_currency)/(pow(10, Currency::$SCALE[$this->default_currency])) . " " . $this->default_currency);
                $output->writeln("Sobre: " . round($this->_exchange($excess, $currency, $this->default_currency),0) . " " . $this->default_currency . " cents");
                $system_data['wallets'][$name]['excess']=$excess;
                $system_data['wallets'][$name]['excess_default']=round($this->_exchange($excess, $currency, $this->default_currency),0);
                /*
                $outs = $wallet_conf->getWaysOut();
                foreach($outs as $out){
                    $way_conf = $this->getContainer()->get($out);
                    $output->writeln("Way min: " . $way_conf->getMinAmount());
                }
                */
            }
        }
        $output->writeln("Info: " . json_encode($system_data));
        $output->writeln("Heuristic: " . $this->heuristic($system_data));
    }

    protected function heuristic($system_data){
        $heuristic = 0;
        foreach($system_data['wallets'] as $wallet){
            if(isset($wallet['need'])){
                $heuristic += ($wallet['need_default'] * $wallet['priority']);
            }
            elseif(isset($wallet['excess'])){
                $heuristic += $wallet['excess_default'];
            }
        }
        foreach($system_data['transfers'] as $transfer){
            $heuristic += $transfer['moneyCost'];
            $heuristic += round($transfer['timeCost']/60,0);
        }
        return $heuristic;
    }

    public function receiving($out){
        $em = $this->getContainer()->get('doctrine')->getManager();
        $transferRepo = $em->getRepository('TelepayFinancialApiBundle:WalletTransfer');
        $list_transfer = array();
        $list = $transferRepo->findBy(
            array('out'=>$out,'status'=>'sending')
        );
        $sum = 0;
        foreach($list as $transfer){
            $sum += $transfer->getAmountOut();
            $timeToEstimated = $transfer->getEstimatedDeliveryTimeStamp() - time();
            $list_transfer[] = array(
                'in' => $transfer->getIn(),
                'out' => $transfer->getOut(),
                'moneyCost' => round($this->_exchange($transfer->getEstimatedCost(), $transfer->getCurrencyOut(), $this->default_currency), 0),
                'timeCost' => $timeToEstimated>0?$timeToEstimated:0
            );
        }
        return array(
            'amount' =>$sum,
            'list' =>$list_transfer
        );
    }

    public function _exchange($amount,$curr_in,$curr_out){
        if($curr_in == $curr_out) return $amount;
        $em = $this->getContainer()->get('doctrine')->getManager();
        $exchangeRepo = $em->getRepository('TelepayFinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$curr_in,'dst'=>$curr_out),
            array('id'=>'DESC')
        );
        if(!$exchange) return 0;
        $price = $exchange->getPrice();
        $total = $amount * $price;
        return $total;
    }

    public function _exchangeInverse($amount,$curr_in,$curr_out){
        if($curr_in == $curr_out) return $amount;
        $em = $this->getContainer()->get('doctrine')->getManager();
        $exchangeRepo = $em->getRepository('TelepayFinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$curr_out,'dst'=>$curr_in),
            array('id'=>'DESC')
        );
        if(!$exchange) return 0;
        $price = 1.0/($exchange->getPrice());
        $total = $amount * $price;
        return $total;
    }
}
