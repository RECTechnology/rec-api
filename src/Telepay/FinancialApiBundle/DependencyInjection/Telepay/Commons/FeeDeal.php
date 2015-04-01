<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons;

use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use Telepay\FinancialApiBundle\Entity\User;

class FeeDeal{
    private $doctrine;
    private $mongo;

    public function __construct($doctrine,$mongo){
        $this->doctrine=$doctrine;
        $this->mongo=$mongo;
    }

    /**
     * Group creator
     * Transaction amount
     * Service name
     * Transaction currency
     * Creator fee
     */

    public function deal(User $creator,$amount,$service_cname,$currency,$fee,$transaction_id){

        if(!$creator->hasRole('ROLE_SUPER_ADMIN')){
            //obtenemos el grupo
            $group=$creator->getGroups()[0];

            //obtener comissiones del grupo
            $commissions=$group->getCommissions();
            $group_commission=false;

            foreach ( $commissions as $commission ){
                if ( $commission->getServiceName() == $service_cname ){
                    $group_commission = $commission;
                }
            }

            $fixed=$group_commission->getFixed();
            $variable=$group_commission->getVariable();
            $total=$fixed+$variable*$amount;
        }else{
            $total=0;
            $variable=0;
            $fixed=0;
        }

        $em = $this->doctrine->getManager();
        $dm = $this->mongo->getManager();

        //Ahora lo añadimos al wallet correspondiente
        $wallets=$creator->getWallets();

        foreach($wallets as $wallet){

            if($wallet->getCurrency() === $currency){

                //Añadimos la pasta al wallet
                $wallet->setAvailable($wallet->getAvailable()+$fee-$total);
                $wallet->setBalance($wallet->getBalance()+$fee-$total);
                $em->persist($wallet);
                $em->flush();
                //$dm = $this->get('doctrine_mongodb')->getManager();
                //$em = $this->getDoctrine()->getManager();

                $transaction = new Transaction();
                $transaction->setIp('127.0.0.1');
                $transaction->setTimeIn(new \MongoDate());
                $transaction->setUser($creator->getId());
                $transaction->setService($service_cname);
                $transaction->setVersion(1);
                $transaction->setAmount($fee-$total);
                $transaction->setDataIn(array(
                    'user' => $creator->getId(),
                    'amount' => doubleval($amount),
                    'service_cname' => $service_cname,
                    'currency' => $currency,
                    'total_fee' => $fee
                ));
                $transaction->setData(array(
                    'parent_id' => $transaction_id
                ));
                //incloure les fees en la transacció
                $transaction->setStatus('success');
                $transaction->setCurrency($currency);
                $transaction->setVariableFee($variable);
                $transaction->setFixedFee($fixed);
                $transaction->setTotal($fee-$total);
                $transaction->setScale($wallet->getScale());
                $dm->persist($transaction);
                $dm->flush();
                $id=$transaction->getId();

            }
        }

        if(!$creator->hasRole('ROLE_SUPER_ADMIN')){

            $new_creator=$group->getCreator();
            $this->deal($new_creator,$amount,$service_cname,$currency,$total,$id);
        }

        return true;

    }
}