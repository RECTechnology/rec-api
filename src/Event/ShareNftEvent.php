<?php

namespace App\Event;

use App\Entity\Challenge;
use App\Entity\Group;

class ShareNftEvent extends BaseNftEvent
{

    const NAME = 'nft.share';

    private $total_transactions;

    private $total_amount;

    public function __construct(Challenge $challenge, $contract_name, Group $sender, Group $receiver, $topic_id, $total_transactions = 0, $total_amount = 0)
    {
        parent::__construct($challenge, $contract_name, $sender, $receiver, $topic_id);
        $this->total_amount = $total_amount;
        $this->total_transactions = $total_transactions;
    }

    /**
     * @return mixed
     */
    public function getTotalTransactions()
    {
        return $this->total_transactions;
    }

    /**
     * @return mixed
     */
    public function getTotalAmount()
    {
        return $this->total_amount;
    }

}