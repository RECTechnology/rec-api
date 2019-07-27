<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserWallet;
use App\FinancialApiBundle\Financial\Currency;

class FeeDeal{
    private $doctrine;
    private $mongo;
    private $balance_manipulator;
    private $container;
    private $fee_logger;

    public function __construct($doctrine, $mongo, $balance_manipulator, $container){
        $this->doctrine = $doctrine;
        $this->mongo = $mongo;
        $this->balance_manipulator = $balance_manipulator;
        $this->container = $container;
        $this->fee_logger = $this->container->get('transaction.logger');
    }

    /**
     * Group creator
     * Transaction amount
     * Service name
     * Transaction currency
     * Creator fee
     */

    public function deal(Group $creator, $amount, $service_cname, $type, $currency, $fee, $transaction_id, $version){

        $this->fee_logger->info('FEE_DEAL (deal)=> amount='.$amount.' service='.$service_cname.' type='.$type.' currency='.$currency.' fee='.$fee.' trans_id='.$transaction_id);

        $rootGroupId = $this->container->getParameter('id_group_root');
        //if creator is distinct to group root
        $cname = explode('_', $service_cname);
        if(isset($cname[0]) && $cname[0] != 'exchange'){
            $cname = $service_cname.'-'.$type;
        }else{
            $cname = $service_cname;
        }

        if($creator->getId() != $rootGroupId){
            $this->fee_logger->info('make transaction -> deal not superadmin CNAME=>'.$cname.' SERVICE NAME=>'.$service_cname.' TYPE=>'.$type);

            //obtener comissiones del grupo
            $commissions = $creator->getCommissions();
            $group_commission = false;

            //get fees for normal methods
            foreach ( $commissions as $commission ){
                if ( $commission->getServiceName() == $cname && $commission->getCurrency() == $currency){
                    $group_commission = $commission;
                }
            }

            $fixed = $group_commission->getFixed();
            $variable = $group_commission->getVariable();

            $this->fee_logger->info('Group(' . $creator->getId() . ') Comission: ' . $fixed . " fixed, " . $variable . " variable");

            $total = round($fixed + ($variable/100) * $amount,0);
        }else{
            $this->fee_logger->info('make transaction -> deal superadmin');
            $total = 0;
            $variable = 0;
            $fixed = 0;
        }

        $em = $this->doctrine->getManager();
        $dm = $this->mongo->getManager();

        //Ahora lo añadimos al wallet correspondiente
        $wallet = $creator->getWallet($currency);

        //Añadimos la pasta al wallet
        $wallet->setAvailable($wallet->getAvailable() + $fee - $total);
        $wallet->setBalance($wallet->getBalance() + $fee - $total);
        $em->persist($wallet);
        $em->flush();

        $scale = $wallet->getScale();

        $price = $this->_getPrice($currency);

        if($fee > 0){
            $this->fee_logger->info('make transaction -> deal sumamos fee');

            $transaction = new Transaction();
            $transaction->setIp('127.0.0.1');
            $transaction->setGroup($creator->getId());
            $transaction->setService($cname);
            $transaction->setMethod($cname);
            $transaction->setVersion($version);
            $transaction->setAmount($fee);
            $transaction->setType(Transaction::$TYPE_FEE);
            $transaction->setDataIn(array(
                'parent_id' => $transaction_id,
                'previous_transaction' => $transaction_id,
                'amount'    =>  $fee,
                'concept'   =>$cname.'->fee'
            ));
            $transaction->setData(array(
                'parent_id' =>  $transaction_id,
                'previous_transaction' =>  $transaction_id,
                'type'      =>  'suma_amount'
            ));
            $feeInfo = array(
                'previous_transaction'  =>  $transaction_id,
                'previous_amount'    =>  $amount,
                'scale'     =>  $scale,
                'concept'           =>  $cname.'->fee',
                'amount' =>  $fee,
                'status'    =>  Transaction::$STATUS_SUCCESS,
                'currency'  =>  $currency
            );
            $transaction->setFeeInfo($feeInfo);
            //incloure les fees en la transacció
            $transaction->setStatus(Transaction::$STATUS_SUCCESS);
            $transaction->setCurrency($currency);
            $transaction->setVariableFee($variable);
            $transaction->setFixedFee($fixed);
            $transaction->setTotal($fee);
            $transaction->setScale($scale);
            $transaction->setPrice($price);

            $dm->persist($transaction);
            $dm->flush();

            $this->fee_logger->info('make transaction -> deal id fee => '.$transaction->getId());
            $this->fee_logger->info('Add Balance->' . $creator->getId() . " amount = " . $fee . " Trans group: " . $transaction->getGroup());
            $this->balance_manipulator->addBalance($creator, $fee, $transaction, "balancer 1");
            $id = $transaction->getId();
        }

        if($creator->getId() != $rootGroupId){
            $this->fee_logger->info('make transaction -> deal not superadmin fee  ');
            if($total > 0){
                $parent_id = 'not generated because 0';
                if($transaction){
                    $parent_id = $transaction->getId();
                }
                $feeTransaction = new Transaction();
                $feeTransaction->setIp('127.0.0.1');
                $feeTransaction->setGroup($creator->getId());
                $feeTransaction->setService($cname);
                $feeTransaction->setMethod($cname);
                $feeTransaction->setType(Transaction::$TYPE_FEE);
                $feeTransaction->setVersion($version);
                $feeTransaction->setAmount($total);
                $feeTransaction->setDataIn(array(
                    'parent_id' => $parent_id,
                    'previous_transaction' => $parent_id,
                    'amount'    =>  -$total,
                    'concept'   =>  $cname.'->fee'
                ));
                $feeTransaction->setData(array(
                    'parent_id' => $parent_id,
                    'type'      =>  'resta_fee'
                ));
                $feeInfo = array(
                    'previous_transaction'  =>  $transaction_id,
                    'previous_amount'    =>  $amount,
                    'scale'     =>  $scale,
                    'concept'           =>  $cname.'->fee',
                    'amount' =>  -$total,
                    'status'    =>  Transaction::$STATUS_SUCCESS,
                    'currency'  =>  $currency
                );
                $feeTransaction->setFeeInfo($feeInfo);
                //incloure les fees en la transacció
                $feeTransaction->setStatus(Transaction::$STATUS_SUCCESS);
                $feeTransaction->setCurrency($currency);
                $feeTransaction->setVariableFee($variable);
                $feeTransaction->setFixedFee($fixed);
                $feeTransaction->setTotal(-$total);
                $feeTransaction->setScale($scale);
                $feeTransaction->setPrice($price);

                $dm->persist($feeTransaction);
                $dm->flush();
                $this->fee_logger->info('make transaction -> deal not superadmin fee id => '.$feeTransaction->getId());
                $this->balance_manipulator->addBalance($creator, -$total, $feeTransaction, "balancer 2");
            }

            $new_creator = $creator->getGroupCreator();
            $this->deal($new_creator, $amount, $service_cname, $type, $currency, $total, $transaction_id, $version);
        }

        return true;

    }

    public function inversedDeal(Group $creator, $amount, $service_cname, $type, $currency, $fee, $transaction_id, $version){

        $logger = $this->container->get('logger');
        $rootGroupId = $this->container->getParameter('id_group_root');

        $group = $creator;

        if($creator->getId() != $rootGroupId){
            $logger->info('make transaction -> inverseddeal not admin ');
            //obtenemos el grupo

            //obtener comissiones del grupo
            $commissions = $group->getCommissions();
            $group_commission = false;

            foreach ( $commissions as $commission ){
                if ( $commission->getServiceName() == $service_cname.'-'.$type && $commission->getCurrency() == $currency){
                    $group_commission = $commission;
                }
            }

            $fixed = $group_commission->getFixed();
            $variable = $group_commission->getVariable();
            $total = $fixed + ($variable/100) * $amount;
        }else{
            $logger->info('make transaction -> inverseddeal admin ');
            $total = 0;
            $variable = 0;
            $fixed = 0;
        }

        $em = $this->doctrine->getManager();
        $dm = $this->mongo->getManager();

        //Ahora lo añadimos al wallet correspondiente
        $wallets = $creator->getWallets();

        $price = $this->_getPrice($currency);
        $scale = 0;
        foreach($wallets as $wallet){

            if($wallet->getCurrency() === $currency){
                $logger->info('make transaction -> deal admin fee');
                //Añadimos la pasta al wallet
                $wallet->setAvailable($wallet->getAvailable() - $fee + $total);
                $wallet->setBalance($wallet->getBalance() - $fee + $total);
                $em->persist($wallet);
                $em->flush();

                $scale = $wallet->getScale();

                $transaction = new Transaction();
                $transaction->setIp('127.0.0.1');
                $transaction->setGroup($creator->getId());
                $transaction->setService($service_cname);
                $transaction->setMethod($service_cname);
                $transaction->setVersion($version);
                $transaction->setAmount($fee);
                $transaction->setType(Transaction::$TYPE_FEE);
                $transaction->setDataIn(array(
                    'parent_id' => $transaction_id,
                    'previous_transaction' => $transaction_id,
                    'amount'    =>  $fee,
                    'concept'   =>$service_cname.'->fee'
                ));
                $transaction->setData(array(
                    'parent_id' =>  $transaction_id,
                    'type'      =>  'refund_fee'
                ));

                $feeInfo = array(
                    'previous_transaction'  =>  $transaction_id,
                    'previous_amount'    =>  $amount,
                    'scale'     =>  $scale,
                    'concept'           =>  'refund '.$service_cname.'->fee',
                    'amount' =>  -$fee,
                    'status'    =>  Transaction::$STATUS_SUCCESS,
                    'currency'  =>  $currency
                );
                $transaction->setFeeInfo($feeInfo);
                //incloure les fees en la transacció
                $transaction->setStatus(Transaction::$STATUS_SUCCESS);
                $transaction->setCurrency($currency);
                $transaction->setVariableFee($variable);
                $transaction->setFixedFee($fixed);
                $transaction->setTotal(-$fee);
                $transaction->setScale($scale);
                $transaction->setPrice($price);

                $dm->persist($transaction);
                $dm->flush();
                $logger->info('make transaction -> deal admin fee id => '.$transaction->getId());
                $this->balance_manipulator->addBalance($creator, -$fee, $transaction, "balancer 3");

                $id = $transaction->getId();

            }
        }

        if($creator->getId() != $rootGroupId){

            $feeTransaction = new Transaction();
            $feeTransaction->setIp('127.0.0.1');
            $feeTransaction->setGroup($creator->getId());
            $feeTransaction->setService($service_cname);
            $feeTransaction->setMethod($service_cname);
            $feeTransaction->setType('fee');
            $feeTransaction->setVersion($version);
            $feeTransaction->setAmount($total);
            $feeTransaction->setDataIn(array(
                'parent_id' => $transaction->getId(),
                'previous_transaction' => $transaction->getId(),
                'amount'    =>  -$total,
                'concept'   =>  $service_cname.'->fee'
            ));
            $feeTransaction->setData(array(
                'parent_id' => $transaction->getId(),
                'type'      =>  'refund_fee'
            ));
            //incloure les fees en la transacció
            $feeTransaction->setStatus(Transaction::$STATUS_SUCCESS);
            $feeTransaction->setCurrency($currency);
            $feeTransaction->setVariableFee($variable);
            $feeTransaction->setFixedFee($fixed);
            $feeTransaction->setTotal($total);
            $feeTransaction->setScale($scale);
            $feeTransaction->setPrice($price);
            $feeInfo = array(
                'previous_transaction'  =>  $transaction_id,
                'previous_amount'    =>  $amount,
                'scale'     =>  $scale,
                'concept'           =>  'refund '.$service_cname.'->fee',
                'amount' =>  $fee,
                'status'    =>  Transaction::$STATUS_SUCCESS,
                'currency'  =>  $currency
            );
            $feeTransaction->setFeeInfo($feeInfo);

            $dm->persist($feeTransaction);
            $dm->flush();
            $logger->info('make transaction -> deal not admin '.$feeTransaction->getId());
            $this->balance_manipulator->addBalance($creator, -$total, $feeTransaction, "balancer 4");

            $new_creator = $group->getGroupCreator();
            $this->inversedDeal($new_creator, $amount, $service_cname, $type, $currency, $total, $id, $version);
        }

        return true;

    }

    public function createFees(Transaction $transaction, UserWallet $current_wallet){
        $this->fee_logger->info('FEE_DEAL (createFees)');
        $amount = $transaction->getAmount();
        $currency = $transaction->getCurrency();
        $service_cname = $transaction->getService();
        $method_cname = $transaction->getMethod();
        $explodeMethod = explode('_', $method_cname);
        if(isset($explodeMethod[0]) && $explodeMethod[0] != 'exchange'){
            $method = $method_cname.'-'.$transaction->getType();
        }else{
            $method = $method_cname;
        }
        $this->fee_logger->info('FEE_DEAL (createFees) => method '.$method);
        $em = $this->doctrine->getManager();
        $this->fee_logger->info('FEE_DEAL (createFees) => getManager ');
        $total_fee = round($transaction->getFixedFee() + $transaction->getVariableFee(),0);
        $this->fee_logger->info('FEE_DEAL (createFees) => fixed '.$transaction->getFixedFee().' variable '.$transaction->getVariableFee());
//        $user = $em->getRepository('FinancialApiBundle:User')->find($transaction->getUser());
        $userGroup = $em->getRepository('FinancialApiBundle:Group')->find($transaction->getGroup());
        $this->fee_logger->info('FEE_DEAL (createFees) => BEFORE TRANSACTION ');

        $mongo = $this->container->get('doctrine_mongodb')->getManager();
        $price = $this->_getPrice($currency);
        if($total_fee != 0){
            $feeTransaction = Transaction::createFromTransaction($transaction);
            $this->fee_logger->info('FEE_DEAL (createFees) => AFTER TRANSACTION ');
            $feeTransaction->setAmount($total_fee);
            $feeTransaction->setDataIn(array(
                'previous_transaction'  =>  $transaction->getId(),
                'amount'                =>  -$total_fee,
                'description'           =>  $method.'->fee'
            ));
            $feeTransaction->setData(array(
                'previous_transaction'  =>  $transaction->getId(),
                'amount'                =>  -$total_fee,
                'type'                  =>  'resta_fee'
            ));
            $feeTransaction->setDebugData(array(
                'previous_balance'  =>  $current_wallet->getBalance(),
                'previous_transaction'  =>  $transaction->getId()
            ));

            $feeTransaction->setTotal(-$total_fee);
            $this->fee_logger->info('FEE_DEAL (createFees) => TYPE ');
            $feeTransaction->setType(Transaction::$TYPE_FEE);
            $feeTransaction->setMethod($method);
            $feeInfo = array(
                'previous_transaction'  =>  $transaction->getId(),
                'previous_amount'    =>  $transaction->getAmount(),
                'scale'     =>  $transaction->getScale(),
                'concept'           =>  $method.'->fee',
                'amount' =>  -$total_fee,
                'status'    =>  Transaction::$STATUS_SUCCESS,
                'currency'  =>  $transaction->getCurrency()
            );
            $feeTransaction->setFeeInfo($feeInfo);
            $feeTransaction->setPrice($price);
            $mongo->persist($feeTransaction);

            $mongo->flush();
            $this->fee_logger->info('FEE_DEAL (createFees) => BALANCE ');
            $balancer = $this->container->get('net.app.commons.balance_manipulator');
            $balancer->addBalance($userGroup, -$total_fee, $feeTransaction, "balancer 5" );

        }

        //restar al wallet
        $current_wallet->setAvailable($current_wallet->getAvailable() - $total_fee);
        $current_wallet->setBalance($current_wallet->getBalance() - $total_fee);

        $em->persist($current_wallet);
        $em->flush();

        //empezamos el reparto
        $creator = $userGroup->getGroupCreator();

        if(!$creator) throw new HttpException(404,'Creator not found');

        $transaction_id = $transaction->getId();
        $this->fee_logger->info('FEE_DEAL (createFees) => GO TO DEAL '.$method_cname);
        $this->deal($creator, $amount, $method_cname, $transaction->getType(), $currency, $total_fee, $transaction_id, $transaction->getVersion());

    }

    public function returnFees(Transaction $transaction, UserWallet $current_wallet){
        $this->fee_logger->info('FEE_DEAL (returnFees)');
        $amount = $transaction->getAmount();
        $currency = $transaction->getCurrency();
        $service_cname = $transaction->getService();
        $method_cname = $transaction->getMethod();
        $explodeMethod = explode('_', $method_cname);
        if(isset($explodeMethod[0]) && $explodeMethod[0] != 'exchange'){
            $method = $method_cname.'-'.$transaction->getType();
        }else{
            $method = $method_cname;
        }
        $this->fee_logger->info('FEE_DEAL (returnFees) => method '.$method);
        $em = $this->doctrine->getManager();
        $this->fee_logger->info('FEE_DEAL (returnFees) => getManager ');
        $total_fee = round($transaction->getFixedFee() + $transaction->getVariableFee(),0);
        $this->fee_logger->info('FEE_DEAL (returnFees) => fixed '.$transaction->getFixedFee().' variable '.$transaction->getVariableFee());
//        $user = $em->getRepository('FinancialApiBundle:User')->find($transaction->getUser());
        $userGroup = $em->getRepository('FinancialApiBundle:Group')->find($transaction->getGroup());
        $this->fee_logger->info('FEE_DEAL (returnFees) => BEFORE TRANSACTION ');

        $mongo = $this->container->get('doctrine_mongodb')->getManager();
        $price = $this->_getPrice($currency);
        if($total_fee != 0){
            $feeTransaction = Transaction::createFromTransaction($transaction);
            $this->fee_logger->info('FEE_DEAL (returnFees) => AFTER TRANSACTION ');
            $feeTransaction->setAmount($total_fee);
            $feeTransaction->setDataIn(array(
                'previous_transaction'  =>  $transaction->getId(),
                'amount'                =>  -$total_fee,
                'description'           =>  $method.'->fee'
            ));
            $feeTransaction->setData(array(
                'previous_transaction'  =>  $transaction->getId(),
                'amount'                =>  -$total_fee,
                'type'                  =>  'resta_fee'
            ));
            $feeTransaction->setDebugData(array(
                'previous_balance'  =>  $current_wallet->getBalance(),
                'previous_transaction'  =>  $transaction->getId()
            ));

            $feeTransaction->setTotal($total_fee);
            $this->fee_logger->info('FEE_DEAL (returnFees) => TYPE ');
            $feeTransaction->setType(Transaction::$TYPE_FEE);
            $feeTransaction->setMethod($method);
            $feeInfo = array(
                'previous_transaction'  =>  $transaction->getId(),
                'previous_amount'    =>  $transaction->getAmount(),
                'scale'     =>  $transaction->getScale(),
                'concept'           =>  'refund '.$method.'->fee',
                'amount' =>  $total_fee,
                'status'    =>  Transaction::$STATUS_SUCCESS,
                'currency'  =>  $transaction->getCurrency()
            );
            $feeTransaction->setFeeInfo($feeInfo);
            $feeTransaction->setPrice($price);
            $mongo->persist($feeTransaction);

            $mongo->flush();
            $this->fee_logger->info('FEE_DEAL (createFees) => BALANCE ');
            $balancer = $this->container->get('net.app.commons.balance_manipulator');
            $balancer->addBalance($userGroup, $total_fee, $feeTransaction, "balancer 6" );

        }

        //sumar al wallet
        $current_wallet->setAvailable($current_wallet->getAvailable() + $total_fee);
        $current_wallet->setBalance($current_wallet->getBalance() + $total_fee);

        $em->persist($current_wallet);
        $em->flush();

        //empezamos el reparto
        $creator = $userGroup->getGroupCreator();

        if(!$creator) throw new HttpException(404,'Creator not found');

        $transaction_id = $transaction->getId();
        $this->fee_logger->info('FEE_DEAL (returnFees) => GO TO DEAL '.$method_cname);
        $this->inversedDeal($creator, $amount, $method_cname, $transaction->getType(), $currency, $total_fee, $transaction_id, $transaction->getVersion());

    }

    private function _getPrice($currency){
        //get price for this currency
        $exchanger = $this->container->get('net.app.commons.exchange_manipulator');
        $price = 1;
        if($currency != Currency::$EUR){
            $price = $exchanger->exchange(pow(10, Currency::$SCALE[$currency]), $currency, 'EUR');
        }

        return $price;
    }

    public function createFees2(Transaction $transaction, UserWallet $current_wallet){
        $this->fee_logger->info('FEE_DEAL (createFees)');
        $amount = $transaction->getAmount();
        $currency = $transaction->getCurrency();
        $method_cname = $transaction->getMethod();
        $explodeMethod = explode('_', $method_cname);
        if(isset($explodeMethod[0]) && $explodeMethod[0] != 'exchange'){
            $method = $method_cname.'-'.$transaction->getType();
        }else{
            $method = $method_cname;
        }
        $this->fee_logger->info('FEE_DEAL (createFees) => method '.$method);
        $em = $this->doctrine->getManager();
        $this->fee_logger->info('FEE_DEAL (createFees) => getManager ');
        $total_fee = round($transaction->getFixedFee() + $transaction->getVariableFee(),0);
        $this->fee_logger->info('FEE_DEAL (createFees) => fixed '.$transaction->getFixedFee().' variable '.$transaction->getVariableFee());
//        $user = $em->getRepository('FinancialApiBundle:User')->find($transaction->getUser());
        $userGroup = $em->getRepository('FinancialApiBundle:Group')->find($transaction->getGroup());
        $this->fee_logger->info('FEE_DEAL (createFees) => BEFORE TRANSACTION ');

        //TODO get root group
        $rootGroupId = $this->container->getParameter('id_group_root');

        $mongo = $this->container->get('doctrine_mongodb')->getManager();
        $price = $this->_getPrice($currency);
        if($total_fee != 0){
            $feeTransaction = Transaction::createFromTransaction($transaction);
            $this->fee_logger->info('FEE_DEAL (createFees) => AFTER TRANSACTION ');
            $feeTransaction->setAmount($total_fee);
            $feeTransaction->setDataIn(array(
                'previous_transaction'  =>  $transaction->getId(),
                'amount'                =>  -$total_fee,
                'description'           =>  $method.'->fee'
            ));
            $feeTransaction->setData(array(
                'previous_transaction'  =>  $transaction->getId(),
                'amount'                =>  -$total_fee,
                'type'                  =>  'resta_fee'
            ));
            $feeTransaction->setDebugData(array(
                'previous_balance'  =>  $current_wallet->getBalance(),
                'previous_transaction'  =>  $transaction->getId()
            ));

            $feeTransaction->setTotal(-$total_fee);
            $this->fee_logger->info('FEE_DEAL (createFees) => TYPE ');
            $feeTransaction->setType(Transaction::$TYPE_FEE);
            $feeTransaction->setMethod($method);
            $feeInfo = array(
                'previous_transaction'  =>  $transaction->getId(),
                'previous_amount'    =>  $transaction->getAmount(),
                'scale'     =>  $transaction->getScale(),
                'concept'           =>  $method.'->fee',
                'amount' =>  -$total_fee,
                'status'    =>  Transaction::$STATUS_SUCCESS,
                'currency'  =>  $transaction->getCurrency()
            );
            $feeTransaction->setFeeInfo($feeInfo);
            $feeTransaction->setPrice($price);
            $mongo->persist($feeTransaction);

            $this->fee_logger->info('FEE_DEAL (createFees) => BALANCE ');

            //restar al wallet
            $current_wallet->setAvailable($current_wallet->getAvailable() - $total_fee);
            $current_wallet->setBalance($current_wallet->getBalance() - $total_fee);

            $em->persist($current_wallet);


            //empezamos el reparto
            //toda la fee a root
            $rootTransaction = new Transaction();
            $rootTransaction->setIp('127.0.0.1');
            $rootTransaction->setGroup($rootGroupId);
            $rootTransaction->setService($feeTransaction->getService());
            $rootTransaction->setMethod($feeTransaction->getMethod());
            $rootTransaction->setVersion($feeTransaction->getVersion());
            $rootTransaction->setAmount($total_fee);
            $rootTransaction->setType(Transaction::$TYPE_FEE);
            $rootTransaction->setDataIn(array(
                'parent_id' => $feeTransaction->getId(),
                'previous_transaction' => $feeTransaction->getId(),
                'amount'    =>  $total_fee,
                'concept'   =>$feeTransaction->getMethod().'->fee'
            ));
            $rootTransaction->setData(array(
                'parent_id' =>  $feeTransaction->getId(),
                'previous_transaction' =>  $feeTransaction->getId(),
                'type'      =>  'suma_amount'
            ));
            $rootFeeInfo = array(
                'previous_transaction'  =>  $feeTransaction->getId(),
                'previous_amount'    =>  $amount,
                'scale'     =>  $feeTransaction->getScale(),
                'concept'           =>  $feeTransaction->getMethod().'->fee',
                'amount' =>  $total_fee,
                'status'    =>  Transaction::$STATUS_SUCCESS,
                'currency'  =>  $currency
            );
            $rootTransaction->setFeeInfo($rootFeeInfo);
            //incloure les fees en la transacció
            $rootTransaction->setStatus(Transaction::$STATUS_SUCCESS);
            $rootTransaction->setCurrency($currency);
            $rootTransaction->setVariableFee($feeTransaction->getVariableFee());
            $rootTransaction->setFixedFee($feeTransaction->getFixedFee());
            $rootTransaction->setTotal($total_fee);
            $rootTransaction->setScale($feeTransaction->getScale());
            $rootTransaction->setPrice($price);

            $this->fee_logger->info('FEE_DEAL (createRootFees) => BALANCE ');
            $rootGroup = $em->getRepository('FinancialApiBundle:Group')->find($rootGroupId);

            //restar al wallet DE ROOT
            $rootWallet = $rootGroup->getWallet($rootTransaction->getCurrency());
            $rootWallet->setAvailable($rootWallet->getAvailable() + $total_fee);
            $rootWallet->setBalance($rootWallet->getBalance() + $total_fee);

            $mongo->persist($rootTransaction);

            $em->flush();
            $mongo->flush();

            $balancer = $this->container->get('net.app.commons.balance_manipulator');
            $balancer->addBalance($userGroup, -$total_fee, $feeTransaction, "balancer 7" );
            $balancer->addBalance($rootGroup, $total_fee, $rootTransaction, "balancer 8" );

            //get all resellerDealer and create transactions
            $resellers = $em->getRepository('FinancialApiBundle:ResellerDealer')->findBy(array(
                'company_origin'    =>  $userGroup,
                'method'    =>  $method
            ));

            $this->fee_logger->info('FEE_DEAL (resellerFees) => total resellers => '.count($resellers));

            foreach ($resellers as $reseller){
                //generate a reseller transaction
                $resellerFee = $total_fee * ($reseller->getFee()/100);
                $this->fee_logger->info('FEE_DEAL (createResellerFees) for '.$reseller->getCompanyReseller()->getName().' -> '.$resellerFee);

                if($resellerFee > 0){
                    $resellerTransaction = new Transaction();
                    $resellerTransaction->setIp('127.0.0.1');
                    $resellerTransaction->setGroup($reseller->getCompanyReseller()->getId());
                    $resellerTransaction->setService($feeTransaction->getService());
                    $resellerTransaction->setMethod($feeTransaction->getMethod());
                    $resellerTransaction->setVersion($feeTransaction->getVersion());
                    //el amount es un porcentaje del total_fee
                    $resellerTransaction->setAmount($resellerFee);
                    $resellerTransaction->setType(Transaction::$TYPE_FEE);
                    $resellerTransaction->setDataIn(array(
                        'parent_id' => $feeTransaction->getId(),
                        'previous_transaction' => $feeTransaction->getId(),
                        'amount'    =>  $total_fee,
                        'concept'   =>$feeTransaction->getMethod().'->fee'
                    ));
                    $resellerTransaction->setData(array(
                        'parent_id' =>  $feeTransaction->getId(),
                        'previous_transaction' =>  $feeTransaction->getId(),
                        'type'      =>  'suma_amount'
                    ));
                    $resellerFeeInfo = array(
                        'previous_transaction'  =>  $feeTransaction->getId(),
                        'previous_amount'    =>  $amount,
                        'scale'     =>  $feeTransaction->getScale(),
                        'concept'           =>  $feeTransaction->getMethod().'->fee',
                        'amount' =>  $resellerFee,
                        'status'    =>  Transaction::$STATUS_SUCCESS,
                        'currency'  =>  $currency
                    );
                    $resellerTransaction->setFeeInfo($resellerFeeInfo);
                    //incloure les fees en la transacció
                    $resellerTransaction->setStatus(Transaction::$STATUS_SUCCESS);
                    $resellerTransaction->setCurrency($currency);
                    $resellerTransaction->setVariableFee($reseller->getFee());
                    $resellerTransaction->setFixedFee(0);
                    $resellerTransaction->setTotal($resellerFee);
                    $resellerTransaction->setScale($feeTransaction->getScale());
                    $resellerTransaction->setPrice($price);

                    $mongo->persist($resellerTransaction);

                    //create a root negative transaction with the same amount
                    $rootResellerTransaction = new Transaction();
                    $rootResellerTransaction->setIp('127.0.0.1');
                    $rootResellerTransaction->setGroup($rootGroupId);
                    $rootResellerTransaction->setService($feeTransaction->getService());
                    $rootResellerTransaction->setMethod($feeTransaction->getMethod());
                    $rootResellerTransaction->setVersion($feeTransaction->getVersion());
                    $rootResellerTransaction->setAmount($resellerFee);
                    $rootResellerTransaction->setType(Transaction::$TYPE_FEE);
                    $rootResellerTransaction->setDataIn(array(
                        'parent_id' => $feeTransaction->getId(),
                        'previous_transaction' => $feeTransaction->getId(),
                        'amount'    =>  $total_fee,
                        'concept'   =>$feeTransaction->getMethod().'->fee'
                    ));
                    $rootResellerTransaction->setData(array(
                        'parent_id' =>  $feeTransaction->getId(),
                        'previous_transaction' =>  $feeTransaction->getId(),
                        'type'      =>  'resta_amount'
                    ));
                    $rootResellerFeeInfo = array(
                        'previous_transaction'  =>  $feeTransaction->getId(),
                        'previous_amount'    =>  $amount,
                        'scale'     =>  $feeTransaction->getScale(),
                        'concept'           =>  $feeTransaction->getMethod().'->fee',
                        'amount' =>  -$resellerFee,
                        'status'    =>  Transaction::$STATUS_SUCCESS,
                        'currency'  =>  $currency
                    );
                    $rootResellerTransaction->setFeeInfo($rootResellerFeeInfo);
                    //incloure les fees en la transacció
                    $rootResellerTransaction->setStatus(Transaction::$STATUS_SUCCESS);
                    $rootResellerTransaction->setCurrency($currency);
                    $rootResellerTransaction->setVariableFee($reseller->getFee());
                    $rootResellerTransaction->setFixedFee(0);
                    $rootResellerTransaction->setTotal(-$resellerFee);
                    $rootResellerTransaction->setScale($feeTransaction->getScale());
                    $rootResellerTransaction->setPrice($price);

                    $mongo->persist($rootResellerTransaction);
                    $mongo->flush();

                    $this->fee_logger->info('FEE_DEAL (createRootFees) => negative ');

                    //restar al wallet
                    $rootWallet->setAvailable($rootWallet->getAvailable() - $resellerFee);
                    $rootWallet->setBalance($rootWallet->getBalance() - $resellerFee);

                    $resellerWallet = $reseller->getCompanyReseller()->getWallet($currency);
                    $resellerWallet->setAvailable($resellerWallet->getAvailable() + $resellerFee);
                    $resellerWallet->setBalance($resellerWallet->getBalance() + $resellerFee);

                    $em->flush();
                    $mongo->flush();

                    $balancer->addBalance($reseller->getCompanyReseller(), $resellerFee, $resellerTransaction, "balancer 9" );
                    $balancer->addBalance($rootGroup, -$resellerFee, $rootResellerTransaction, "balancer 10" );

                }

            }

        }

    }

}