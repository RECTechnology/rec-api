<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons;

use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Balance;
use Telepay\FinancialApiBundle\Entity\User;

class BalanceManipulator{
    private $doctrine;

    public function __construct($doctrine){
        $this->doctrine = $doctrine;
    }

    /**
     * User
     * Transaction amount (+/-)
     * Transaction
     */

    public function addBalance(User $user, $amount, Transaction $transaction){

        $em = $this->doctrine->getManager();
        $prev_balance = $em->getRepository('TelepayFinancialApiBundle:Balance')
            ->findOneBy(array(
                    'user'  =>  $transaction->getUser(),
                    'currency'  =>  $transaction->getCurrency()
                ),
                array(
                    'id'    =>  'DESC'
                )
            );

        if(!$prev_balance){
            $balance = new Balance();
            $balance->setUser($user);
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

        if($transaction->getDataIn()['description']){
            $concept = $transaction->getDataIn()['description'];
        }else{
            $concept = $transaction->getDataIn()['concept'];
        }
        $balance = new Balance();
        $balance->setUser($user);
        $balance->setAmount($amount);
        $balance->setBalance($prev_balance + $amount);
        $balance->setConcept($concept);
        $balance->setCurrency($transaction->getCurrency());
        $balance->setDate(new \DateTime());
        $balance->setTransactionId($transaction->getId());

        $em->persist($balance);
        $em->flush();

        return $balance;

    }

}