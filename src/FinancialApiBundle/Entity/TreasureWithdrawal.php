<?php

namespace App\FinancialApiBundle\Entity;

use App\FinancialApiBundle\Annotations\HybridProperty;
use App\FinancialApiBundle\Annotations\StatusProperty;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\ORM\Mapping as ORM;
use App\FinancialApiBundle\Document\Transaction;

/**
 * Class TreasureWithdrawal
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity()
 */
class TreasureWithdrawal extends AppObject implements Stateful, HybridPersistent {

    const MINIMUM_TREASURE_WITHDRAWAL_VALIDATIONS_RATE = 0.5;
    const TREASURE_WITHDRAWAL_EXPIRATION_INTERVAL = "+1 day";
    const TREASURE_WITHDRAWAL_STATUS_APPROVED = "approved";
    const TREASURE_WITHDRAWAL_STATUS_PENDING = "pending";
    const TREASURE_WITHDRAWAL_STATUS_REJECTED = "rejected";

    use StatefulTrait;

    /**
     * @ORM\Column(type="string")
     * @StatusProperty(
     *     initial="created",
     *     choices={
     *          "created"={"to"={"pending"}},
     *          "pending"={"to"={"approved", "expired"}},
     *          "approved"={"final"=true},
     *          "expired"={"final"=true}
     *      }
     * )
     * @Serializer\Groups({"admin"})
     */
    private $status;

    /**
     * @var Transaction $transaction
     * @HybridProperty(
     *     targetEntity="App\FinancialApiBundle\Document\Transaction",
     *     identifier="transaction_id",
     *     manager="doctrine.odm.mongodb.document_manager"
     * )
     */
    private $transaction;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Exclude()
     */
    private $transaction_id;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"admin"})
     */
    private $amount;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $expires_at;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"admin"})
     */
    private $description;


    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\TreasureWithdrawalValidation", mappedBy="withdrawal")
     * @Serializer\Groups({"admin"})
     */
    private $validations;

    /**
     * TreasureWithdrawal constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->validations = new ArrayCollection();
        //$expirationInterval = DateInterval::createfromdatestring(self::TREASURE_WITHDRAWAL_EXPIRATION_INTERVAL);
        //$this->expires_at = $this->getCreated()->add($expirationInterval);
    }

    /**
     * @Serializer\VirtualProperty(name="status")
     * @Serializer\Type("string")
     * @Serializer\Groups({"admin"})
     */
    public function getStatus(){
        if($this->isApproved()) return self::TREASURE_WITHDRAWAL_STATUS_APPROVED;
        elseif($this->getExpiresAt() < new \DateTime()) return self::TREASURE_WITHDRAWAL_STATUS_PENDING;
        return self::TREASURE_WITHDRAWAL_STATUS_REJECTED;
    }

    /**
     * @Serializer\VirtualProperty(name="approved")
     * @Serializer\Type("boolean")
     * @Serializer\Groups({"admin"})
     */
    public function isApproved(){
        $minimum_validations = floor($this->validations->count() * self::MINIMUM_TREASURE_WITHDRAWAL_VALIDATIONS_RATE);
        $validation_count = 0;
        /** @var TreasureWithdrawalValidation $validation */
        foreach ($this->validations as $validation) {
            if($validation->isAccepted()) {
                if(++$validation_count > $minimum_validations)
                    return true;
            }
        }
        return false;
    }


    /**
     * @Serializer\VirtualProperty(name="sent")
     * @Serializer\Type("boolean")
     * @Serializer\Groups({"admin"})
     */
    public function isSent(){
        return $this->getTransaction() != null and $this->getTransaction()->getStatus() !== "failed";
    }

    /**
     * @return mixed
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * @param mixed $transaction
     */
    public function setTransaction($transaction)
    {
        $this->transaction = $transaction;
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
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getExpiresAt()
    {
        return $this->expires_at;
    }

    /**
     * @param mixed $validation
     */
    public function addValidation($validation)
    {
        $this->validations []= $validation;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }
}