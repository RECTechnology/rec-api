<?php

namespace App\FinancialApiBundle\Event;

use App\FinancialApiBundle\Entity\Challenge;
use App\FinancialApiBundle\Entity\Group;
use Symfony\Component\EventDispatcher\Event;

class BaseNftEvent extends Event
{
    private $topic_id;

    private $contract_name;

    private Group $receiver;

    private Group $sender;

    private Challenge $challenge;

    public function __construct(Challenge $challenge, $contract_name, Group $sender, Group $receiver, $topic_id){
        $this->contract_name = $contract_name;
        $this->receiver = $receiver;
        $this->topic_id = $topic_id;
        $this->challenge = $challenge;
        $this->sender = $sender;
    }

    public function getOriginalToken(){
        return $this->challenge->getTokenReward()->getTokenId();
    }

    public function getContractName(){
        return $this->contract_name;
    }

    public function getSender(): Group
    {
        return $this->sender;
    }

    public function getReceiver(): Group
    {
        return $this->receiver;
    }

    public function getTopicId(){
        return $this->topic_id;
    }

    public function getTokenReward(){
        return $this->challenge->getTokenReward();
    }

    public function getChallenge(){
        return $this->challenge;
    }
}