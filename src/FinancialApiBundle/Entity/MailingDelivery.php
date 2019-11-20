<?php
/**
 *  Author: Lluis Santos
 *  Date: 24 Aug 2019
 */

namespace App\FinancialApiBundle\Entity;

use App\FinancialApiBundle\Annotations as REC;
use App\FinancialApiBundle\Validator\Constraint as RECAssert;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Mailing
 * @package App\FinancialApiBundle\Entity
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"account_id", "mailing_id"})})
 * @ORM\Entity
 */
class MailingDelivery extends AppObject implements Stateful {

    const STATUS_SCHEDULED = "scheduled";
    const STATUS_SENT = "sent";
    const STATUS_DELIVERED = "delivered";
    const STATUS_OPENED = "opened";
    const STATUS_UNSUBSCRIBED = "unsubscribed";
    const STATUS_CANCELLED = "cancelled";
    const STATUS_ERRORED = "errored";
    const STATUS_FAILED = "failed";
    const STATUS_COMPLAINED = "complained";

    /**
     * @ORM\Column(type="string")
     * @REC\StatusProperty(
     *     choices={
     *          "created"={"to"={"scheduled", "cancelled"}},
     *          "scheduled"={"to"={"sent", "errored", "cancelled", "created"}},
     *          "sent"={"to"={"delivered", "failed", "errored"}},
     *          "delivered"={"to"={"opened", "complained", "unsubscribed"}},
     *          "opened"={"to"={"complained", "unsubscribed"}},
     *          "failed"={"final"=true},
     *          "complained"={"to"={"unsubscribed"}},
     *          "unsubscribed"={"to"={"complained"}},
     *          "cancelled"={"final"=true},
     *          "errored"={"final"=true}
     *     },
     *     initial="created"
     * )
     * @Serializer\Groups({"admin"})
     */
    private $status;

    /**
     * @ORM\Column(type="string", nullable=true, unique=true)
     * @Serializer\Groups({"admin"})
     */
    private $message_ref;


    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $failure_reason;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group")
     * @RECAssert\HasValidEmail()
     * @Serializer\Groups({"admin"})
     */
    private $account;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Mailing", inversedBy="deliveries")
     * @Serializer\Groups({"admin"})
     */
    private $mailing;

    /**
     * Activity constructor.
     */
    public function __construct() {
        $this->status = self::STATUS_CREATED;
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
    public function getMailing()
    {
        return $this->mailing;
    }

    /**
     * @param mixed $mailing
     */
    public function setMailing($mailing): void
    {
        $this->mailing = $mailing;
    }

    /**
     * @return mixed
     */
    public function getMessageRef()
    {
        return $this->message_ref;
    }

    /**
     * @param mixed $message_ref
     */
    public function setMessageRef($message_ref): void
    {
        $this->message_ref = $message_ref;
    }

    /**
     * @return mixed
     */
    public function getFailureReason()
    {
        return $this->failure_reason;
    }

    /**
     * @param mixed $failure_reason
     */
    public function setFailureReason($failure_reason): void
    {
        $this->failure_reason = $failure_reason;
    }

}