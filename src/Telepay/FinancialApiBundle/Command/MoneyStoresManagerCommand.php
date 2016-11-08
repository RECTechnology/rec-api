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
use Fhaculty\Graph\Graph;
use Graphp\Algorithms;

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

        $graph = new Graph();
        // create some cities
        $rome = $graph->createVertex('Rome');
        $madrid = $graph->createVertex('Madrid');
        $cologne = $graph->createVertex('Cologne');
        // build some roads
        $cologne->createEdgeTo($madrid)->setWeight(10);
        $madrid->createEdgeTo($rome)->setWeight(-1);


        $alg = $this->createAlg($v1);
        $alg->getEdges();

        $em = $this->getContainer()->get('doctrine')->getManager();
        $walletRepo = $em->getRepository("TelepayFinancialApiBundle:WalletConf");
        $wallets = $walletRepo->findAll();
        foreach ($wallets as $wallet) {
            $type = $wallet->getType();
            $currency = $wallet->getCurrency();
            $wallet_conf = $this->getContainer()->get('net.telepay.wallet.' . $type . '.' . $currency);
            $currency = strtoupper($currency);
            $balance = $balances[$currency]['balance'];
            $min = round($wallet->getMinBalance() * $balance / 100 + $wallet->getFixedAmount(),0);
            $max = round($wallet->getMaxBalance() * $balance / 100 + $wallet->getFixedAmount(),0);
            $perfect = round($wallet->getPerfectBalance() * $balance / 100 + $wallet->getFixedAmount(),0);
            $now = round($wallet_conf->getBalance() * (pow(10, $SCALE[$currency])), 0); // + receiving
            $output->writeln("Now: " . $now);
            if($now < $min){
                $need = $perfect - $now;
                $output->writeln("Need: " . $need);
            }
            elseif($now > $max){
                $sobre = $now - $perfect;
                $output->writeln("Sobre: " . $sobre);
                $outs = $wallet_conf->getWaysOut();
                foreach($outs as $out){
                    $way_conf = $this->getContainer()->get($out);
                    $output->writeln("Way min: " . $way_conf->getMinAmount());
                }
            }
        }
    }
}