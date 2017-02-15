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
use Telepay\FinancialApiBundle\Entity\Group;
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

    public function addBalance(Group $group, $amount, Transaction $transaction){

        $em = $this->doctrine->getManager();
        $prev_balance = $em->getRepository('TelepayFinancialApiBundle:Balance')
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
        $balance->setTransactionId($transaction->getId());

        $em->persist($balance);
        $em->flush();

        return $balance;

    }

}