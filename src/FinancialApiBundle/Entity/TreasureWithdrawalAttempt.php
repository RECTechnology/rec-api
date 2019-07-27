<?php

namespace App\FinancialApiBundle\Entity;
use DateInterval;
use Doctrine\Common\Collections\ArrayCollection;
use function Sodium\add;
use Symfony\Component\HttpKernel\Exception\HttpException;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\SerializedName;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\FinancialApiBundle\Document\Transaction;


/**
 * @ORM\Entity
 * @ExclusionPolicy("all")
 */
class TreasureWithdrawalAttempt extends AppObject {

    const MINIMUM_TREASURE_WITHDRAWAL_VALIDATIONS_RATE = 0.5;
    const TREASURE_WITHDRAWAL_EXPIRATION_INTERVAL = "+1 day";
    const TREASURE_WITHDRAWAL_STATUS_APPROVED = "approved";
    const TREASURE_WITHDRAWAL_STATUS_PENDING = "pending";
    const TREASURE_WITHDRAWAL_STATUS_REJECTED = "rejected";

    /**
     * TreasureWithdrawalAttempt constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->validations = new ArrayCollection();
        $expirationInterval = DateInterval::createfromdatestring(self::TREASURE_WITHDRAWAL_EXPIRATION_INTERVAL);
        $this->expires_at = $this->getCreated()->add($expirationInterval);
    }

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $transaction_id;

    /**
     * @ORM\Column(type="integer")
     * @Expose()
     */
    private $amount;

    /**
     * @ORM\Column(type="datetime")
     * @Expose()
     */
    private $expires_at;

    /**
     * @ORM\Column(type="string")
     * @Expose()
     */
    private $description;

    /**
     * @var Transaction $transaction
     * @Expose
     */
    private $transaction;

    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\TreasureWithdrawalValidation", mappedBy="attempt")
     * @Expose()
     */
    private $validations;

    /**
     * @VirtualProperty()
     * @Type("string")
     * @SerializedName("status")
     */
    public function getStatus(){
        if($this->isApproved()) return self::TREASURE_WITHDRAWAL_STATUS_APPROVED;
        elseif($this->getExpiresAt() < new \DateTime()) return self::TREASURE_WITHDRAWAL_STATUS_PENDING;
        return self::TREASURE_WITHDRAWAL_STATUS_REJECTED;
    }

    /**
     * @VirtualProperty()
     * @Type("boolean")
     * @SerializedName("approved")
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
     * @VirtualProperty()
     * @Type("boolean")
     * @SerializedName("sent")
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
}