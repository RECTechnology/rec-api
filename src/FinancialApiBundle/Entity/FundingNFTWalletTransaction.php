<?php

namespace App\FinancialApiBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\MaxDepth;

/**
 * Class FundingNFTWalletTransaction
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class FundingNFTWalletTransaction extends AppObject
{
    public const STATUS_CREATED = 'created';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_FAILED = 'failed';

    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCESS = 'success';

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"admin"})
     */
    private $status;

    /**
     * This account who receive the amount
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group")
     * @Serializer\Groups({"admin"})
     * @MaxDepth(1)
     */
    private $account;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $tx_id;

    /**
     * @ORM\Column(type="bigint")
     * @Serializer\Groups({"admin"})
     */
    private $amount;

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getTxId()
    {
        return $this->tx_id;
    }

    /**
     * @param mixed $tx_id
     */
    public function setTxId($tx_id): void
    {
        $this->tx_id = $tx_id;
    }

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
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount): void
    {
        $this->amount = $amount;
    }

}