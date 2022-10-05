<?php

namespace App\FinancialApiBundle\Event;

use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Group;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Event;

class TransferEvent extends Event
{
    const NAME = 'transfer.success';

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

}