<?php

namespace App\FinancialApiBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\MaxDepth;

/**
 * Class NFTTransactions
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class NFTTransaction extends AppObject
{
    public const NFT_MINT = 'mint';
    public const NFT_SHARE = 'share';
    public const NFT_LIKE = 'like';
    public const NFT_UNLIKE = 'unlike';
    public const NFT_BURN = 'burn';

    public const STATUS_CREATED = 'created';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_FUNDING_PENDING = 'funding_pending';

    public const B2B_SHARABLE_CONTRACT = 'b2b_sharable_contract';
    public const B2B_LIKE_CONTRACT = 'b2b_like_contract';
    public const B2C_SHARABLE_CONTRACT = 'b2c_sharable_contract';

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"admin"})
     */
    private $method;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"admin"})
     */
    private $status;

    /**
     * This account is who execute contract
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group")
     * @Serializer\Groups({"admin"})
     * @MaxDepth(1)
     */
    private $from;

    /**
     * This account is who receive token
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group")
     * @Serializer\Groups({"admin"})
     * @MaxDepth(1)
     */
    private $to;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $tx_id;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $topic_id;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $contract_name;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $original_token_id;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $shared_token_id;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\TokenReward")
     * @Serializer\Groups({"admin"})
     * @MaxDepth(1)
     */
    private $token_reward;

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     */
    public function setMethod($method): void
    {
        $this->method = $method;
    }

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
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param mixed $from
     */
    public function setFrom($from): void
    {
        $this->from = $from;
    }

    /**
     * @return mixed
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param mixed $to
     */
    public function setTo($to): void
    {
        $this->to = $to;
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
    public function getTopicId()
    {
        return $this->topic_id;
    }

    /**
     * @param mixed $topic_id
     */
    public function setTopicId($topic_id): void
    {
        $this->topic_id = $topic_id;
    }

    /**
     * @return mixed
     */
    public function getOriginalTokenId()
    {
        return $this->original_token_id;
    }

    /**
     * @param mixed $original_token_id
     */
    public function setOriginalTokenId($original_token_id): void
    {
        $this->original_token_id = $original_token_id;
    }

    /**
     * @return mixed
     */
    public function getSharedTokenId()
    {
        return $this->shared_token_id;
    }

    /**
     * @param mixed $shared_token_id
     */
    public function setSharedTokenId($shared_token_id): void
    {
        $this->shared_token_id = $shared_token_id;
    }

    /**
     * @return mixed
     */
    public function getContractName()
    {
        return $this->contract_name;
    }

    /**
     * @param mixed $contract_name
     */
    public function setContractName($contract_name): void
    {
        $this->contract_name = $contract_name;
    }

    /**
     * @return mixed
     */
    public function getTokenReward()
    {
        return $this->token_reward;
    }

    /**
     * @param mixed $token_reward
     */
    public function setTokenReward($token_reward): void
    {
        $this->token_reward = $token_reward;
    }

}