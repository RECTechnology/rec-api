<?php
namespace App\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\FinancialApiBundle\Financial\Currency;
use App\FinancialApiBundle\Financial\BagNode;
use App\FinancialApiBundle\Entity\WalletTransfer;

class MoneyStoresManagerCommand extends ContainerAwareCommand{
    protected function configure(){
        $this
            ->setName('rec:money_store:manager')
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
                'max-range',
                null,
                InputOption::VALUE_REQUIRED,
                'Define the max range of heuristic value for nodes.',
                null
            )
            ->addOption(
                'send',
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
    public $maxHeuristicRange;

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
            $this->maxSteps = 5;
        }

        if($input->getOption('max-range')){
            $this->maxHeuristicRange = strtoupper($input->getOption('max-range'));
        }
        else{
            $this->maxHeuristicRange = 10;
        }

        if($input->getOption('send')){
            $this->test = false;
        }
        else{
            $this->test = true;
        }

        $em = $this->getContainer()->get('doctrine')->getManager();
        $groupRepo = $em->getRepository('FinancialApiBundle:Group');
        $groups = $groupRepo->findBy(
            array('own'=>true)
        );
        $chipchap_groups = array();
        foreach($groups as $group){
            $chipchap_groups[] = $group->getId();
        }

        $qb = $this->getContainer()->get('doctrine')->getRepository('FinancialApiBundle:UserWallet')->createQueryBuilder('w');
        $qb->Select('SUM(w.balance) as balance, w.currency')
            ->where('w.group NOT IN (:groups)')
            ->setParameter('groups', $chipchap_groups)
            ->groupBy('w.currency');

        $query = $qb->getQuery()->getResult();

        $balances = [];
        foreach($query as $balance){
            $balance['balance'] = round($balance['balance'],0);
            $balance['scale'] = Currency::$SCALE[$balance['currency']];
            $balances[$balance['currency']] = $balance;
        }
        $output->writeln("Balances users: " . json_encode($balances));

        $system_data = array();
        $system_data['wallets'] = array();
        $system_data['transfers'] = array();
        $system_data['transfers_to_do'] = array();

        $em = $this->getContainer()->get('doctrine')->getManager();
        $walletRepo = $em->getRepository("FinancialApiBundle:WalletConf");
        $wallets = $walletRepo->findAll();
        foreach ($wallets as $wallet) {
            $type = $wallet->getType();
            $currency = strtoupper($wallet->getCurrency());
            $name = $type . '_' . $currency;
            $system_data['wallets'][$name]['conf']=$wallet;
            $wallet_conf = $this->getContainer()->get('net.app.wallet.' . $type . '.' . $currency);
            $system_data['wallets'][$name]['wallet_conf']=$wallet_conf;
            $currency = strtoupper($currency);
            $balance = $balances[$currency]['balance'];
            $min = round($wallet->getMinBalance() * $balance / 100 + $wallet->getFixedAmount(),0);
            $max = round($wallet->getMaxBalance() * $balance / 100 + $wallet->getFixedAmount(),0);
            $perfect = round($wallet->getPerfectBalance() * $balance / 100 + $wallet->getFixedAmount(),0);
            $now = round($wallet_conf->getBalance() * (pow(10, Currency::$SCALE[$currency])), 0);
            if($this->test){
                $output->writeln("Balance " . $name . ": " . $now/pow(10, Currency::$SCALE[$currency]) . " " . $currency);
            }
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
        $output->writeln("Init: " . json_encode($bestNode->getInfo()));

        array_push($listNodes, $initNode);
        while(count($listNodes)>0){
            $node = array_shift($listNodes);
            if($node->getHeuristic() < $bestNode->getHeuristic()){
                $bestNode = $node;
            }
            if($node->getSteps()<$this->maxSteps && $node->getHeuristic()<$bestNode->getHeuristic()*$this->maxHeuristicRange){
                $listPossibleTransfers = $this->possibleTransfers($node->getInfo(), false, $output);
                foreach($listPossibleTransfers as $possibleTransfer){
                    $new = $this->generateNewNode($node, $possibleTransfer);
                    array_push($listNodes, $new);
                }
            }
        }

        $listTransfersToDo = $this->mergeTransfers($bestNode->getInfo()['transfers_to_do']);
        if($this->test){
            $output->writeln("Best: " . json_encode($bestNode->getInfo()));
            $output->writeln("Transfers to do: " . json_encode($listTransfersToDo));
            $output->writeln("H: " . $bestNode->getHeuristic());
        }
        else{
            $this->sendList($listTransfersToDo);
        }
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

    public function sendList($list){
        $em = $this->getContainer()->get('doctrine')->getManager();
        foreach($list as $type=>$transfer){
            $way_conf = $this->getContainer()->get('net.app.link.' . strtolower($transfer['origin']) . '.' . strtolower($transfer['destination']));
            $amount = $transfer['amount'];
            $amount_origin = $amount;
            $amount_destination = $amount;
            if($way_conf->getStartNode()->getCurrency() != $this->default_currency){
                $amount_origin = round($this->_exchange($amount, $this->default_currency, $way_conf->getStartNode()->getCurrency()), 0);
            }
            if($way_conf->getEndNode()->getCurrency() != $this->default_currency){
                $amount_destination = round($this->_exchange($amount, $this->default_currency, $way_conf->getEndNode()->getCurrency()), 0);
            }
            $transfer_data = new WalletTransfer();
            $transfer_data->setType($type);
            $transfer_data->setStatus('sending');
            $transfer_data->setCurrencyOrigin(strtoupper($way_conf->getStartNode()->getCurrency()));
            $transfer_data->setAmountOrigin($amount_origin);
            $transfer_data->setCurrencyDestination(strtoupper($way_conf->getEndNode()->getCurrency()));
            $transfer_data->setAmountDestination($amount_destination);
            $transfer_data->setSentTimeStamp(time());
            $transfer_data->setEstimatedDeliveryTimeStamp($way_conf->getEstimatedDeliveryTime());
            $transfer_data->setEstimatedCost($way_conf->getEstimatedCost($amount_origin));
            $transfer_data->setWalletOrigin(strtolower($transfer['origin']));
            $transfer_data->setWalletDestination(strtolower($transfer['destination']));
            $data_of_transfer = $way_conf->send($amount);
            if($data_of_transfer['sent']){
                $transfer_data->setInformation($data_of_transfer['info']);
                $em->persist($transfer_data);
            }
        }
        $em->flush();
    }

    public function receiving($wallet){
        $destination = $wallet->getType() . '_' . $wallet->getCurrency();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $transferRepo = $em->getRepository('FinancialApiBundle:WalletTransfer');
        $list_transfer = array();
        $list = $transferRepo->findBy(
            array('walletDestination'=>$destination,'status'=>'sending')
        );
        $sum = 0;
        foreach($list as $transfer){
            $sum += $transfer->getAmountDestination();
            $timeEstimated = $transfer->getEstimatedDeliveryTimeStamp() - time();
            if($timeEstimated < 0)$timeEstimated=0;
            $deadline = $wallet->getMaxTime();
            if( $deadline > 0 && $timeEstimated > $deadline)$timeEstimated=60000000;
            $list_transfer[] = array(
                'origin' => $transfer->getWalletOrigin(),
                'destination' => $transfer->getWalletDestination(),
                'amount' => round($this->_exchange($transfer->getAmountDestination(), $transfer->getCurrencyDestination(), $this->default_currency), 0),
                'moneyCost' => round($this->_exchange($transfer->getEstimatedCost(), $transfer->getCurrencyDestination(), $this->default_currency), 0),
                'timeCost' => $timeEstimated
            );
        }
        return array(
            'amount' =>$sum,
            'list' =>$list_transfer
        );
    }

    private function possibleTransfers($info, $print, $output){
        $listPossibleTransfersNeed = array();
        $listPossibleTransfersExcess = array();
        foreach($info['wallets'] as $wallet){
            if(isset($wallet['need_default'])){
                $need = $wallet['need_default'];
                $destination_name = $wallet['wallet_conf']->getType() . '_' . $wallet['wallet_conf']->getCurrency();
                $origins = $wallet['wallet_conf']->getWaysIn();
                foreach($origins as $origin_data){
                    $way_conf = $this->getContainer()->get($origin_data);
                    $origin = $way_conf->getStartNode();
                    $type = $origin->getType();
                    $currency = $origin->getCurrency();
                    $origin_name = $type . '_' . $currency;
                    if(isset($info['wallets'][$origin_name])){
                        $maxAmount = $info['wallets'][$origin_name]['now_default'];
                        $minAmount = round($this->_exchange($way_conf->getMinAmount(), $way_conf->getStartNode()->getCurrency(), $this->default_currency),0);
                        $available = $info['wallets'][$origin_name]['now_default'] + $info['wallets'][$origin_name]['receiving_default'] - $info['wallets'][$origin_name]['perfect_default'];
                        $available = min($available, $info['wallets'][$origin_name]['now_default']);
                        //origen in perfect status
                        if($available >= $minAmount) {
                            $listPossibleTransfersNeed[] = array(
                                'origin' => $origin_name,
                                'destination' => $destination_name,
                                'amount' => $available,
                                'info' => '1'
                            );
                        }
                        //what destination need
                        if($need > $minAmount && $need < $maxAmount){
                            $listPossibleTransfersNeed[] = array(
                                'origin' => $origin_name,
                                'destination' => $destination_name,
                                'amount' => $need,
                                'info' => '2'
                            );
                        }
                        //destination with more than perfect amount
                        if(($need + $minAmount) < $maxAmount){
                            $listPossibleTransfersNeed[] = array(
                                'origin' => $origin_name,
                                'destination' => $destination_name,
                                'amount' => $need + $minAmount,
                                'info' => '3'
                            );
                        }
                        //origin in min status
                        if($maxAmount > $minAmount){
                            $listPossibleTransfersNeed[] = array(
                                'origin' => $origin_name,
                                'destination' => $destination_name,
                                'amount' => $maxAmount,
                                'info' => '4'
                            );
                        }
                    }
                }
            }
            elseif(isset($wallet['excess_default'])){
                $excess = $wallet['excess_default'];
                $origin_name = $wallet['wallet_conf']->getType() . '_' . $wallet['wallet_conf']->getCurrency();
                $destinations = $wallet['wallet_conf']->getWaysOut();
                foreach($destinations as $destination_data){
                    $way_conf = $this->getContainer()->get($destination_data);
                    $destination = $way_conf->getEndNode();
                    $type = $destination->getType();
                    $currency = $destination->getCurrency();
                    $destination_name = $type . '_' . $currency;
                    if(isset($info['wallets'][$destination_name])){
                        $max_receive = $info['wallets'][$destination_name]['max_default'] - $info['wallets'][$destination_name]['now_default'] - $info['wallets'][$destination_name]['receiving_default'];
                        $want_receive = $info['wallets'][$destination_name]['perfect_default'] - $info['wallets'][$destination_name]['now_default'] - $info['wallets'][$destination_name]['receiving_default'];
                        $minAmount = round($this->_exchange($way_conf->getMinAmount(), $way_conf->getStartNode()->getCurrency(), $this->default_currency),0);
                        $maxAmount = $info['wallets'][$origin_name]['now_default'];

                        //Send all the excess available
                        $excess = min($maxAmount, $excess);
                        if($excess >= $minAmount){
                            $listPossibleTransfersExcess[] = array(
                                'origin' => $origin_name,
                                'destination' => $destination_name,
                                'amount' => $excess,
                                'info' => '5'
                            );
                        }

                        if(!$info['wallets'][$destination_name]['conf']->isStorehouse()){
                            //Destination wallet to perfect status
                            if($want_receive > $minAmount && $want_receive < $maxAmount){
                                $listPossibleTransfersExcess[] = array(
                                    'origin' => $origin_name,
                                    'destination' => $destination_name,
                                    'amount' => $want_receive,
                                    'info' => '6'
                                );
                            }

                            //Destination wallet to max status
                            if($max_receive > $minAmount && $max_receive < $maxAmount){
                                $listPossibleTransfersExcess[] = array(
                                    'origin' => $origin_name,
                                    'destination' => $destination_name,
                                    'amount' => $max_receive,
                                    'info' => '7'
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
            if(isset($result[$transfer['origin'] . '-' . $transfer['destination']])){
                $result[$transfer['origin'] . '-' . $transfer['destination']]['amount'] += $transfer['amount'];
            }
            elseif(isset($result[$transfer['destination'] . '-' . $transfer['origin']])){
                $result[$transfer['destination'] . '-' . $transfer['origin']]['amount'] -= $transfer['amount'];
                if($result[$transfer['destination'] . '-' . $transfer['origin']]['amount'] < 0){
                    $result[$transfer['origin'] . '-' . $transfer['destination']] = abs($result[$transfer['destination'] . '-' . $transfer['origin']]);
                    unset($result[$transfer['destination'] . '-' . $transfer['origin']]);
                }
            }
            else{
                $result[$transfer['origin'] . '-' . $transfer['destination']] = $transfer;
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

        $way_conf = $this->getContainer()->get('net.app.link.' . strtolower($transfer['origin']) . '.' . strtolower($transfer['destination']));
        $info['transfers_to_do'][] = array(
            'origin' => $transfer['origin'],
            'destination' => $transfer['destination'],
            'amount' => $transfer['amount'],
            'moneyCost' => round($transfer['amount'] * $way_conf->getMoneyCost() / 100, 0),
            'timeCost' => 3600,
            'type' => $transfer['info']
        );

        $heuristic = $this->heuristic($info);
        $newNode = new BagNode();
        $newNode->defineValues($info, $heuristic, $node->getSteps() + 1);
        $newNode->setPrev($node);
        $newNode->setTransfer($transfer);
        return $newNode;
    }

    public function _exchange($amount,$curr_origin,$curr_destination){
        if($curr_origin == $curr_destination) return $amount;
        $em = $this->getContainer()->get('doctrine')->getManager();
        $exchangeRepo = $em->getRepository('FinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$curr_origin,'dst'=>$curr_destination),
            array('id'=>'DESC')
        );
        if(!$exchange) return 0;
        $price = $exchange->getPrice();
        $total = $amount * $price;
        return $total;
    }

    public function _exchangeInverse($amount,$curr_origin,$curr_destination){
        if($curr_origin == $curr_destination) return $amount;
        $em = $this->getContainer()->get('doctrine')->getManager();
        $exchangeRepo = $em->getRepository('FinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$curr_destination,'dst'=>$curr_origin),
            array('id'=>'DESC')
        );
        if(!$exchange) return 0;
        $price = 1.0/($exchange->getPrice());
        $total = $amount * $price;
        return $total;
    }
}
