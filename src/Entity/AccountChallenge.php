<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\MaxDepth;

/**
 * Class AccountChallenge
 * @package App\Entity
 * @ORM\Entity
 */
class AccountChallenge extends AppObject
{

    /**
     * This account is who get the award
     * @ORM\ManyToOne(targetEntity="App\Entity\Group")
     * @Serializer\Groups({"user"})
     * @MaxDepth(2)
     */
    private $account;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Challenge")
     * @Serializer\Groups({"user"})
     * @MaxDepth(2)
     */
    private $challenge;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     * @Serializer\Groups({"user"})
     */
    private $total_amount;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Serializer\Groups({"user"})
     */
    private $total_transactions;

    /**
     * @return mixed
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param mixed $account
     */
    public function setAccount($account): void
    {
        $this->account = $account;
    }

    /**
     * @return mixed
     */
    public function getChallenge()
    {
        return $this->challenge;
    }

    /**
     * @param mixed $challenge
     */
    public function setChallenge($challenge): void
    {
        $this->challenge = $challenge;
    }

    /**
     * @return mixed
     */
    public function getTotalAmount()
    {
        return $this->total_amount;
    }

    /**
     * @param mixed $total_amount
     */
    public function setTotalAmount($total_amount): void
    {
        $this->total_amount = $total_amount;
    }

    /**
     * @return mixed
     */
    public function getTotalTransactions()
    {
        return $this->total_transactions;
    }

    /**
     * @param mixed $total_transactions
     */
    public function setTotalTransactions($total_transactions): void
    {
        $this->total_transactions = $total_transactions;
    }

}