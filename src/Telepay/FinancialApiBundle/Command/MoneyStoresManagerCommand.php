<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Telepay\FinancialApiBundle\Financial\MiniumBalanceInterface;
use WebSocket\Client;
use WebSocket\Exception;

class MoneyStoresManagerCommand extends ContainerAwareCommand{
    protected function configure(){
        $this
            ->setName('telepay:money_store:manager')
            ->setDescription('Guarantees the minimum amount in each registered money storage')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){
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
        $SCALE = array(
            "BTC" => 8,
            "EUR" => 2,
            "USD" => 2,
            "FAC" => 8,
            "MXN" => 2,
            "PLN" => 2
        );

        foreach($query as $balance){
            $balance['available'] = round($balance['available'],0);
            $balance['balance'] = round($balance['balance'],0);
            $balance['scale'] = $SCALE[$balance['currency']];
            $balances[$balance['currency']] = $balance;
        }

        $system_data = array();
        $system_data['wallets'] = array();
        $system_data['ways'] = array();

        $em = $this->getContainer()->get('doctrine')->getManager();
        $walletRepo = $em->getRepository("TelepayFinancialApiBundle:WalletConf");
        $wallets = $walletRepo->findAll();
        foreach ($wallets as $wallet) {
            $type = $wallet->getType();
            $currency = $wallet->getCurrency();
            $system_data['wallets'][$type]['priority']=$wallet->getPriority();
            $system_data['wallets'][$type]['currency']=$currency;
            $wallet_conf = $this->getContainer()->get('net.telepay.wallet.' . $type . '.' . $currency);
            $currency = strtoupper($currency);
            $balance = $balances[$currency]['balance'];
            $min = round($wallet->getMinBalance() * $balance / 100 + $wallet->getFixedAmount(),0);
            $max = round($wallet->getMaxBalance() * $balance / 100 + $wallet->getFixedAmount(),0);
            $perfect = round($wallet->getPerfectBalance() * $balance / 100 + $wallet->getFixedAmount(),0);
            $now = round($wallet_conf->getBalance() * (pow(10, $SCALE[$currency])), 0); // + receiving
            $output->writeln("Now: " . $now/(pow(10, $SCALE[$currency]) . $currency));
            if($now < $min){
                $need = $perfect - $now;
                $output->writeln("Need: " . $need/(pow(10, $SCALE[$currency]) . $currency));
                $system_data['wallets'][$type]['need']=$need;
            }
            elseif($now > $max){
                $excess = $now - $perfect;
                $output->writeln("Sobre: " . $excess/(pow(10, $SCALE[$currency]) . $currency));
                $system_data['wallets'][$type]['excess']=$excess;
                /*
                $outs = $wallet_conf->getWaysOut();
                foreach($outs as $out){
                    $way_conf = $this->getContainer()->get($out);
                    $output->writeln("Way min: " . $way_conf->getMinAmount());
                }
                */
            }
        }
    }
}