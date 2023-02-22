<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace App\DependencyInjection\Commons;

use App\Document\Transaction;
use App\Entity\Balance;
use App\Entity\Group;

class BalanceManipulator{
    private $doctrine;

    public function __construct($doctrine){
        $this->doctrine = $doctrine;
    }

    /**
     * User
     * Transaction amount (+/-)
     * Transaction
     * @param Group $group
     * @param $amount
     * @param Transaction $transaction
     * @param string $log
     * @return Balance
     */

    public function addBalance(Group $group, $amount, Transaction $transaction, $log = 'null'){

        $em = $this->doctrine->getManager();
        $prev_balance = $em->getRepository('FinancialApiBundle:Balance')
            ->findOneBy(array(
                    'group'  =>  $transaction->getGroup(),
                    'currency'  =>  $transaction->getCurrency()
                ),
                array(
                    'id'    =>  'DESC'
                )
            );

        if(!$prev_balance){
            $balance = new Balance();
            $balance->setGroup($group);
            $balance->setAmount(0);
            $balance->setBalance(0);
            $balance->setConcept('Start transaction');
            $balance->setCurrency($transaction->getCurrency());
            $balance->setDate(new \DateTime());
            $balance->setTransactionId(0);

            $em->persist($balance);
            $em->flush();
            $prev_balance = 0;
        }else{
            $prev_balance = $prev_balance->getBalance();
        }

        if(isset($transaction->getPayInInfo()['concept'])){
            $concept = $transaction->getPayInInfo()['concept'];
        }else if(isset($transaction->getPayOutInfo()['concept'])){
            $concept = $transaction->getPayOutInfo()['concept'];
        }else if(isset($transaction->getDataIn()['concept'])){
            $concept = $transaction->getDataIn()['concept'];
        }else if(isset($transaction->getFeeInfo()['concept'])){
            $concept = $transaction->getFeeInfo()['concept'];
        }else{
            $concept = 'Default content';
        }

        $balance = new Balance();
        $balance->setGroup($group);
        $balance->setAmount($amount);
        $balance->setBalance($prev_balance + $amount);
        $balance->setConcept($concept);
        $balance->setCurrency($transaction->getCurrency());
        $balance->setDate(new \DateTime());
        $balance->setLog($log);
        $balance->setTransactionId($transaction->getId());

        $em->persist($balance);
        $em->flush();

        return $balance;

    }

}