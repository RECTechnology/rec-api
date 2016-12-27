<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Telepay\FinancialApiBundle\Financial\Currency;
use Telepay\FinancialApiBundle\Financial\BagNode;

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
            ->addOption(
                'max-steps',
                null,
                InputOption::VALUE_REQUIRED,
                'Define the max depth of tree algorithm.',
                null
            )
            ->addOption(
                'test',
                null,
                InputOption::VALUE_REQUIRED,
                'Test mode active.',
                null
            )
        ;
    }

    public $default_currency;
    public $test;
    public $maxSteps;

    protected function execute(InputInterface $input, OutputInterface $output){
        if($input->getOption('currency')){
            $this->default_currency = strtoupper($input->getOption('currency'));
        }
        else{
            $this->default_currency = 'EUR';
        }

        if($input->getOption('max-steps')){
            $this->maxSteps = strtoupper($input->getOption('max-steps'));
        }
        else{
            $this->maxSteps = 10;
        }

        if($input->getOption('test')){
            $this->test = true;
        }
        else{
            $this->test = false;
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
        $system_data['transfers_to_do'] = array();

        $em = $this->getContainer()->get('doctrine')->getManager();
        $walletRepo = $em->getRepository("TelepayFinancialApiBundle:WalletConf");
        $wallets = $walletRepo->findAll();
        foreach ($wallets as $wallet) {
            $type = $wallet->getType();
            $currency = strtoupper($wallet->getCurrency());
            $name = $type . '_' . $currency;
            $system_data['wallets'][$name]['conf']=$wallet;
            $wallet_conf = $this->getContainer()->get('net.telepay.wallet.' . $type . '.' . $currency);
            $system_data['wallets'][$name]['wallet_conf']=$wallet_conf;
            $currency = strtoupper($currency);
            $balance = $balances[$currency]['balance'];
            $min = round($wallet->getMinBalance() * $balance / 100 + $wallet->getFixedAmount(),0);
            $max = round($wallet->getMaxBalance() * $balance / 100 + $wallet->getFixedAmount(),0);
            $perfect = round($wallet->getPerfectBalance() * $balance / 100 + $wallet->getFixedAmount(),0);
            $now = round($wallet_conf->getFakeBalance() * (pow(10, Currency::$SCALE[$currency])), 0);
            $receiving_data = $this->receiving($wallet);
            $receiving = $receiving_data['amount'];
            $system_data['transfers'] = array_merge($system_data['transfers'], $receiving_data['list']);
            $system_data['wallets'][$name]['now_default']=round($this->_exchange($now, $currency, $this->default_currency),0);
            $system_data['wallets'][$name]['receiving_default']=round($this->_exchange($receiving, $currency, $this->default_currency),0);
            $system_data['wallets'][$name]['perfect_default']=round($this->_exchange($perfect, $currency, $this->default_currency),0);
            $system_data['wallets'][$name]['min_default']=round($this->_exchange($min, $currency, $this->default_currency),0);
            $system_data['wallets'][$name]['max_default']=round($this->_exchange($max, $currency, $this->default_currency),0);

            if($now + $receiving < $min){
                $need = $perfect - $now - $receiving;
                $system_data['wallets'][$name]['need_default']=round($this->_exchange($need, $currency, $this->default_currency),0);
            }
            elseif($now + $receiving > $max){
                $excess = $now + $receiving - $perfect;
                $system_data['wallets'][$name]['excess_default']=round($this->_exchange($excess, $currency, $this->default_currency),0);
            }
        }
        $heuristic = $this->heuristic($system_data);
        $listNodes = array();
        $initNode = new BagNode();
        $initNode->defineValues($system_data, $heuristic, 0);
        $bestNode = $initNode;
        $output->writeln("Best: " . json_encode($bestNode->getInfo()));

        array_push($listNodes, $initNode);
        while(count($listNodes)>0){
            $node = array_shift($listNodes);
            if($node->getHeuristic() < $bestNode->getHeuristic()){
                $bestNode = $node;
            }
            if($node->getSteps() < $this->maxSteps){
                $listPossibleTransfers = $this->possibleTransfers($node->getInfo());
                foreach($listPossibleTransfers as $possibleTransfer){
                    $new = $this->generateNewNode($node, $possibleTransfer);
                    array_push($listNodes, $new);
                }
            }
        }
        //$output->writeln("");
        //$output->writeln("Best: " . json_encode($bestNode->getInfo()));
        //$output->writeln("Best h: " . json_encode($bestNode->getHeuristic()));
        //$output->writeln("Prev: " . json_encode($bestNode->getPrev()->getInfo()));
        //$output->writeln("Prev h: " . json_encode($bestNode->getPrev()->getHeuristic()));
        //$output->writeln("Prev t: " . json_encode($this->possibleTransfers($bestNode->getPrev()->getInfo())));

        $listTransfersToDo = $this->mergeTransfers($bestNode->getInfo()['transfers_to_do']);
        $output->writeln("To do: " . json_encode($listTransfersToDo));
    }

    protected function heuristic($system_data){
        $heuristic = 0;
        foreach($system_data['wallets'] as $wallet){
            if(isset($wallet['need_default'])){
                $heuristic += round($wallet['need_default'] * (1/$wallet['conf']->getPriority()),0);
            }
            elseif(isset($wallet['excess_default']) && !$wallet['conf']->isStorehouse()){
                $heuristic += round($wallet['excess_default']/10,0);
            }
        }
        foreach($system_data['transfers'] as $transfer){
            $heuristic += $transfer['moneyCost'];
            $heuristic += round($transfer['timeCost']/60,0);
        }
        foreach($system_data['transfers_to_do'] as $transfer){
            $heuristic += $transfer['moneyCost'];
            $heuristic += round($transfer['timeCost']/60,0);
        }
        return $heuristic;
    }

    public function send($wallet_conf, $amount){
        $outs = $wallet_conf->getWaysOut();
        foreach($outs as $out){
            $way_conf = $this->getContainer()->get($out);
            if($amount > $way_conf->getMinAmount());
        }
    }

    public function receiving($wallet){
        $out = $wallet->getType() . '_' . $wallet->getCurrency();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $transferRepo = $em->getRepository('TelepayFinancialApiBundle:WalletTransfer');
        $list_transfer = array();
        $list = $transferRepo->findBy(
            array('wallet_out'=>$out,'status'=>'sending')
        );
        $sum = 0;
        foreach($list as $transfer){
            $sum += $transfer->getAmountOut();
            $timeEstimated = $transfer->getEstimatedDeliveryTimeStamp() - time();
            if($timeEstimated < 0)$timeEstimated=0;
            $deadline = $wallet->getMaxTime();
            if( $deadline > 0 && $timeEstimated > $deadline)$timeEstimated=60000000;
            $list_transfer[] = array(
                'in' => $transfer->getWalletIn(),
                'out' => $transfer->getWalletOut(),
                'amount' => round($this->_exchange($transfer->getAmountOut(), $transfer->getCurrencyOut(), $this->default_currency), 0),
                'moneyCost' => round($this->_exchange($transfer->getEstimatedCost(), $transfer->getCurrencyOut(), $this->default_currency), 0),
                'timeCost' => $timeEstimated
            );
        }
        return array(
            'amount' =>$sum,
            'list' =>$list_transfer
        );
    }

    private function possibleTransfers($info){
        $listPossibleTransfersNeed = array();
        $listPossibleTransfersExcess = array();
        foreach($info['wallets'] as $wallet){
            if(isset($wallet['need_default'])){
                $need = $wallet['need_default'];
                $destination_name = $wallet['wallet_conf']->getType() . '_' . $wallet['wallet_conf']->getCurrency();
                $ins = $wallet['wallet_conf']->getWaysIn();
                foreach($ins as $in){
                    $way_conf = $this->getContainer()->get($in);
                    $origin = $way_conf->getStartNode();
                    $type = $origin->getType();
                    $currency = $origin->getCurrency();
                    $origin_name = $type . '_' . $currency;
                    if(isset($info['wallets'][$origin_name])){
                        if($info['wallets'][$origin_name]['now_default'] + $info['wallets'][$origin_name]['receiving_default'] > $info['wallets'][$origin_name]['perfect_default']){
                            $available = $info['wallets'][$origin_name]['now_default'] + $info['wallets'][$origin_name]['receiving_default'] - $info['wallets'][$origin_name]['perfect_default'];
                            $available = min($available, $info['wallets'][$origin_name]['now_default']);
                            //origen in perfect status
                            $minAmount = round($this->_exchange($way_conf->getMinAmount(), $way_conf->getStartNode()->getCurrency(), $this->default_currency),0);
                            if($available >= $minAmount) {
                                $listPossibleTransfersNeed[] = array(
                                    'origin' => $origin_name,
                                    'destination' => $destination_name,
                                    'amount' => $available
                                );
                            }
                            if($need > $available && $available < $info['wallets'][$origin_name]['now_default']){
                                //origen in min status
                                $max_available = $info['wallets'][$origin_name]['now_default'] + $info['wallets'][$origin_name]['receiving_default'] - $info['wallets'][$origin_name]['min_default'];
                                $max_available = min($max_available, $need, $info['wallets'][$origin_name]['now_default']);
                                if($max_available >= $minAmount){
                                    $listPossibleTransfersNeed[] = array(
                                        'origin' => $origin_name,
                                        'destination' => $destination_name,
                                        'amount' => $max_available
                                    );
                                }
                            }
                        }
                    }
                }
            }
            elseif(isset($wallet['excess_default'])){
                $excess = $wallet['excess_default'];
                $origin_name = $wallet['wallet_conf']->getType() . '_' . $wallet['wallet_conf']->getCurrency();
                $outs = $wallet['wallet_conf']->getWaysOut();
                foreach($outs as $out){
                    $way_conf = $this->getContainer()->get($out);
                    $destination = $way_conf->getEndNode();
                    $type = $destination->getType();
                    $currency = $destination->getCurrency();
                    $destination_name = $type . '_' . $currency;
                    if(isset($info['wallets'][$destination_name])){
                        $can_receive = $info['wallets'][$destination_name]['max_default'] - $info['wallets'][$destination_name]['now_default'] - $info['wallets'][$destination_name]['receiving_default'];
                        if($info['wallets'][$destination_name]['conf']->isStorehouse()) $can_receive = $excess;
                        $minAmount = round($this->_exchange($way_conf->getMinAmount(), $way_conf->getStartNode()->getCurrency(), $this->default_currency),0);
                        $maxAmount = $info['wallets'][$origin_name]['now_default'];
                        if($can_receive >= $excess && $excess >= $minAmount && $excess <= $maxAmount){
                            $listPossibleTransfersExcess[] = array(
                                'origin' => $origin_name,
                                'destination' => $destination_name,
                                'amount' => $excess
                            );
                        }
                        else{
                            //Destination wallet to max status
                            if($can_receive >= $minAmount && $can_receive <= $maxAmount){
                                $listPossibleTransfersExcess[] = array(
                                    'origin' => $origin_name,
                                    'destination' => $destination_name,
                                    'amount' => $can_receive
                                );
                            }
                            //Destination wallet to exceed status
                            $max_receive = $can_receive + $info['wallets'][$destination_name]['min_default'];
                            $max_receive = min($excess, $max_receive);
                            if($max_receive >= $minAmount && $excess <= $maxAmount){
                                $listPossibleTransfersExcess[] = array(
                                    'origin' => $origin_name,
                                    'destination' => $destination_name,
                                    'amount' => $max_receive
                                );
                            }
                        }
                    }
                }
            }
        }
        if(count($listPossibleTransfersNeed)==0){
            return $listPossibleTransfersExcess;
        }
        return $listPossibleTransfersNeed;
    }

    public function mergeTransfers($listTransfers){
        $result = array();
        foreach($listTransfers as $transfer){
            if(isset($result[$transfer['in'] . '-' . $transfer['out']])){
                $result[$transfer['in'] . '-' . $transfer['out']]['amount'] += $transfer['amount'];
            }
            else{
                $result[$transfer['in'] . '-' . $transfer['out']] = $transfer;
            }
        }
        return $result;
    }

    public function generateNewNode($node, $transfer){
        $info = $node->getInfo();
        $origen_name = $transfer['origin'];
        $info['wallets'][$origen_name]['now_default']-=$transfer['amount'];
        if(isset($info['wallets'][$origen_name]['excess_default'])){
            $info['wallets'][$origen_name]['excess_default']-=$transfer['amount'];
            if($info['wallets'][$origen_name]['excess_default']<=0){
                unset($info['wallets'][$origen_name]['excess_default']);
            }
        }
        $origen_data = $info['wallets'][$origen_name];
        if($origen_data['now_default'] + $origen_data['receiving_default'] < $origen_data['min_default']){
            $info['wallets'][$origen_name]['need_default'] = $origen_data['perfect_default'] - $origen_data['now_default'] - $origen_data['receiving_default'];
        }

        $destination_name = $transfer['destination'];
        $info['wallets'][$destination_name]['receiving_default']+=$transfer['amount'];
        if(isset($info['wallets'][$destination_name]['need_default'])){
            $info['wallets'][$destination_name]['need_default']-=$transfer['amount'];
            if($info['wallets'][$destination_name]['need_default']<=0){
                unset($info['wallets'][$destination_name]['need_default']);
            }
        }
        $destination_data = $info['wallets'][$destination_name];
        if($destination_data['now_default'] + $destination_data['receiving_default'] > $destination_data['max_default']){
            $info['wallets'][$destination_name]['excess_default'] = $destination_data['now_default'] + $destination_data['receiving_default'] - $destination_data['perfect_default'];
        }

        $info['transfers_to_do'][] = array(
            'in' => $transfer['origin'],
            'out' => $transfer['destination'],
            'amount' => $transfer['amount'],
            'moneyCost' => round($transfer['amount'] / 100, 0),
            'timeCost' => 3600
        );

        $heuristic = $this->heuristic($info);
        $newNode = new BagNode();
        $newNode->defineValues($info, $heuristic, $node->getSteps() + 1);
        $newNode->setPrev($node);
        $newNode->setTransfer($transfer);
        return $newNode;
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
