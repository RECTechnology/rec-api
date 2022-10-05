<?php

namespace App\FinancialApiBundle\Event;

use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Group;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Event;

class PurchaseSuccessEvent extends Event
{
    const NAME = 'purchase.success';

    protected $transaction;

    /** @var EntityManagerInterface $em */
    protected $em;

    public function __construct(Transaction $transaction, EntityManagerInterface $em){
        $this->transaction = $transaction;
        $this->em = $em;
    }

    public function getTransaction(){
        return $this->transaction;
    }

    public function getAccount(): Group
    {
        /** @var Group $account */
        $account = $this->em->getRepository(Group::class)->find($this->transaction->getGroup());
        return $account;
    }

    public function getReceiver(): Group
    {
        $payOutInfo = $this->transaction->getPayOutInfo();
        $receiver_id = $payOutInfo['receiver_id'];
        /** @var Group $receiver */
        $receiver = $this->em->getRepository(Group::class)->find($receiver_id);
        return $receiver;
    }

}