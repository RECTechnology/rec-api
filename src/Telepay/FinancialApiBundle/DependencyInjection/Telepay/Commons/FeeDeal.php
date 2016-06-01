<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons;

use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\User;

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
        $rootGroupId = $this->container->getParameter('id_group_root');

        //if creator is distinct to group root
        if($creator->getId() != $rootGroupId){

            //obtenemos el grupo
            $group = $creator->getGroups()[0];

            //obtener comissiones del grupo
            $commissions = $group->getCommissions();
            $group_commission = false;

            if($type != 'exchange'){
                $service_cname = $service_cname.'-'.$type;
            }
            //get fees for normal methods
            foreach ( $commissions as $commission ){
                if ( $commission->getServiceName() == $service_cname && $commission->getCurrency() == $currency){
                    $group_commission = $commission;
                }
            }

            $fixed = $group_commission->getFixed();
            $variable = $group_commission->getVariable();
            $total = $fixed + ($variable/100) * $amount;
        }else{
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

                //Añadimos la pasta al wallet
                $wallet->setAvailable($wallet->getAvailable() + $fee - $total);
                $wallet->setBalance($wallet->getBalance() + $fee - $total);
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
                    'amount'    =>  $fee,
                    'description'   =>$service_cname.'->fee'
                ));
                $transaction->setData(array(
                    'parent_id' =>  $transaction_id,
                    'type'      =>  'suma_amount'
                ));
                //incloure les fees en la transacció
                $transaction->setStatus('success');
                $transaction->setCurrency($currency);
                $transaction->setVariableFee($variable);
                $transaction->setFixedFee($fixed);
                $transaction->setTotal($fee);
                $transaction->setScale($scale);

                $dm->persist($transaction);
                $dm->flush();

                $this->balance_manipulator->addBalance($creator, $fee, $transaction);

                $id = $transaction->getId();

            }else{
                $id = 0;
            }
        }

        if($creator->getId() != $rootGroupId){
            if($total > 0){
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
                    'amount'    =>  -$total,
                    'description'   =>  $service_cname.'->fee'
                ));
                $feeTransaction->setData(array(
                    'parent_id' => $transaction->getId(),
                    'type'      =>  'resta_fee'
                ));
                //incloure les fees en la transacció
                $feeTransaction->setStatus('success');
                $feeTransaction->setCurrency($currency);
                $feeTransaction->setVariableFee($variable);
                $feeTransaction->setFixedFee($fixed);
                $feeTransaction->setTotal(-$total);
                $feeTransaction->setScale($scale);

                $dm->persist($feeTransaction);
                $dm->flush();

                $this->balance_manipulator->addBalance($creator, -$total, $feeTransaction);
            }

            $new_creator = $creator->getGroupCreator();
            $this->deal($new_creator, $amount, $service_cname, $type, $currency, $total, $id, $version);
        }

        return true;

    }

    public function inversedDeal(Group $creator, $amount, $service_cname, $type, $currency, $fee, $transaction_id, $version){

        $rootGroupId = $this->doctrine->getParameter('id_group_root');

        $group = $creator->getGroups()[0];

        if($creator->getId() != $rootGroupId){
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
                    'amount'    =>  $fee,
                    'description'   =>$service_cname.'->fee'
                ));
                $transaction->setData(array(
                    'parent_id' =>  $transaction_id,
                    'type'      =>  'refund_fee'
                ));
                $transaction->setPayOutInfo(array(
                    'parent_id' => $transaction_id,
                    'amount'    =>  -$fee,
                    'description'   => 'refund'.$service_cname.'->fee'
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
                'amount'    =>  -$total,
                'description'   =>  $service_cname.'->fee'
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

            $this->balance_manipulator->addBalance($creator, -$total, $feeTransaction);

            $new_creator = $group->getGroupCreator();
            $this->inversedDeal($new_creator, $amount, $service_cname, $type, $currency, $total, $id, $version);
        }

        return true;

    }

}