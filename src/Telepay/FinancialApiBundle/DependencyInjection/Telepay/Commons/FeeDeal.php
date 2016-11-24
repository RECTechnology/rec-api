<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\Currency;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\User;
use Telepay\FinancialApiBundle\Entity\UserWallet;

class FeeDeal{
    private $doctrine;
    private $mongo;
    private $balance_manipulator;
    private $container;

    public function __construct($doctrine, $mongo, $balance_manipulator, $container){
        $this->doctrine = $doctrine;
        $this->mongo = $mongo;
        $this->balance_manipulator = $balance_manipulator;
        $this->container = $container;
    }

    /**
     * Group creator
     * Transaction amount
     * Service name
     * Transaction currency
     * Creator fee
     */

    public function deal(Group $creator, $amount, $service_cname, $type, $currency, $fee, $transaction_id, $version){

        //TODO hay que cambiar esto porque ya no va al user superadmin si no al grupo root
        $logger = $this->container->get('transaction.logger');
        $rootGroupId = $this->container->getParameter('id_group_root');
        //if creator is distinct to group root
        $cname = $service_cname;
        if($type != 'exchange'){
            $cname = $service_cname.'-'.$type;
        }

        if($creator->getId() != $rootGroupId){
            $logger->info('make transaction -> deal not superadmin');

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

            $logger->info('Group(' . $creator->getId() . ') Comission: ' . $fixed . " fixed, " . $variable . " variable");

            $total = round($fixed + ($variable/100) * $amount,0);
        }else{
            $logger->info('make transaction -> deal superadmin');
            $total = 0;
            $variable = 0;
            $fixed = 0;
        }

        $em = $this->doctrine->getManager();
        $dm = $this->mongo->getManager();

        //Ahora lo añadimos al wallet correspondiente
        $wallets = $creator->getWallets();

        $scale = 0;
        foreach($wallets as $wallet){

            if($wallet->getCurrency() === $currency && $fee > 0){
                $logger->info('make transaction -> deal sumamos fee');
                //Añadimos la pasta al wallet
                $wallet->setAvailable($wallet->getAvailable() + $fee - $total);
                $wallet->setBalance($wallet->getBalance() + $fee - $total);
                $em->persist($wallet);
                $em->flush();

                $scale = $wallet->getScale();

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

                $dm->persist($transaction);
                $dm->flush();

                $logger->info('make transaction -> deal id fee => '.$transaction->getId());
                $logger->info('Add Balance->' . $creator->getId() . " amount = " . $fee . " Trans group: " . $transaction->getGroup());
                $this->balance_manipulator->addBalance($creator, $fee, $transaction);
                $id = $transaction->getId();
            }
        }

        if($creator->getId() != $rootGroupId){
            $logger->info('make transaction -> deal not superadmin fee  ');
            if($total > 0){
                $feeTransaction = new Transaction();
                $feeTransaction->setIp('127.0.0.1');
                $feeTransaction->setGroup($creator->getId());
                $feeTransaction->setService($cname);
                $feeTransaction->setMethod($cname);
                $feeTransaction->setType(Transaction::$TYPE_FEE);
                $feeTransaction->setVersion($version);
                $feeTransaction->setAmount($total);
                $feeTransaction->setDataIn(array(
                    'parent_id' => $transaction->getId(),
                    'previous_transaction' => $transaction->getId(),
                    'amount'    =>  -$total,
                    'concept'   =>  $cname.'->fee'
                ));
                $feeTransaction->setData(array(
                    'parent_id' => $transaction->getId(),
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

                $dm->persist($feeTransaction);
                $dm->flush();
                $logger->info('make transaction -> deal not superadmin fee id => '.$feeTransaction->getId());
                $this->balance_manipulator->addBalance($creator, -$total, $feeTransaction);
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
                $transaction->setType('fee');
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
                $transaction->setPayOutInfo(array(
                    'parent_id' => $transaction_id,
                    'previous_transaction' => $transaction_id,
                    'amount'    =>  -$fee,
                    'concept'   => 'refund'.$service_cname.'->fee'
                ));

                //incloure les fees en la transacció
                $transaction->setStatus('success');
                $transaction->setCurrency($currency);
                $transaction->setVariableFee($variable);
                $transaction->setFixedFee($fixed);
                $transaction->setTotal(-$fee);
                $transaction->setScale($scale);

                $dm->persist($transaction);
                $dm->flush();
                $logger->info('make transaction -> deal admin fee id => '.$transaction->getId());
                $this->balance_manipulator->addBalance($creator, -$fee, $transaction);

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
            $feeTransaction->setStatus('success');
            $feeTransaction->setCurrency($currency);
            $feeTransaction->setVariableFee($variable);
            $feeTransaction->setFixedFee($fixed);
            $feeTransaction->setTotal($total);
            $feeTransaction->setScale($scale);

            $dm->persist($feeTransaction);
            $dm->flush();
            $logger->info('make transaction -> deal not admin '.$feeTransaction->getId());
            $this->balance_manipulator->addBalance($creator, -$total, $feeTransaction);

            $new_creator = $group->getGroupCreator();
            $this->inversedDeal($new_creator, $amount, $service_cname, $type, $currency, $total, $id, $version);
        }

        return true;

    }

    public function createFees(Transaction $transaction, UserWallet $current_wallet){

        $amount = $transaction->getAmount();
        $currency = $transaction->getCurrency();
        $service_cname = $transaction->getService();
        $method_cname = $transaction->getMethod();
        $method = $method_cname.'-'.$transaction->getType();

        $em = $this->doctrine->getManager();

        $total_fee = round($transaction->getFixedFee() + $transaction->getVariableFee(),0);

        $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($transaction->getUser());
        $userGroup = $em->getRepository('TelepayFinancialApiBundle:Group')->find($transaction->getGroup());

        $feeTransaction = Transaction::createFromTransaction($transaction);
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

        $feeTransaction->setType('fee');
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

        $mongo = $this->container->get('doctrine_mongodb')->getManager();
        $mongo->persist($feeTransaction);
        $mongo->flush();

        $balancer = $this->container->get('net.telepay.commons.balance_manipulator');
        $balancer->addBalance($userGroup, -$total_fee, $feeTransaction );

        //empezamos el reparto
        $creator = $userGroup->getGroupCreator();

        if(!$creator) throw new HttpException(404,'Creator not found');

        $transaction_id = $transaction->getId();

        $this->deal($creator, $amount, $service_cname, 'exchange', $currency, $total_fee, $transaction_id, $transaction->getVersion());

    }
}