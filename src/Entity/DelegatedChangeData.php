<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation as Serializer;

use Symfony\Component\Validator\Constraints as Assert;
use App\Document\Transaction;
use App\Validator\Constraint as RECAssert;

/**
 * @ORM\Entity
 * @ORM\Table(name="delegated_change_data")
 */
class DelegatedChangeData extends AppObject {

    const STATUS_CREATED = "created";
    const STATUS_SUCCESS = "success";
    const STATUS_ERROR = "error";

    public function __construct()
    {
        $this->status = "new";
    }

    /**
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="App\Entity\DelegatedChange", inversedBy="data")
     * @Serializer\Groups({"admin"})
     * @Serializer\MaxDepth(1)
     */
    private $delegated_change;


    /**
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @RECAssert\IsUser()
     * @ORM\ManyToOne(targetEntity="App\Entity\Group")
     * @Serializer\Groups({"admin"})
     * @Serializer\MaxDepth(1)
     */
    private $account;

    /**
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @RECAssert\IsCommerce()
     * @ORM\ManyToOne(targetEntity="App\Entity\Group")
     * @Serializer\Groups({"admin"})
     * @Serializer\MaxDepth(1)
     */
    private $exchanger;

    /**
     * @Assert\NotNull
     * @Assert\GreaterThan(0)
     * @ORM\Column(type="float", nullable=true)
     * @Serializer\Groups({"admin"})
     * @Serializer\MaxDepth(1)
     */
    private $amount;

    /**
     * @Assert\NotNull
     * @ORM\Column(type="string")
     * @Serializer\Groups({"admin"})
     */
    private $status;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $transaction_ref;


    private $transaction;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CreditCard")
     * @Serializer\Groups({"admin"})
     * @Serializer\MaxDepth(1)
     */
    private $creditcard;


    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Group")
     * @Serializer\Groups({"admin"})
     */
    private $sender;


    /**
     * @return mixed
     */
    public function getDelegatedChange()
    {
        return $this->delegated_change;
    }

    /**
     * @param mixed $delegated_change
     */
    public function setDelegatedChange($delegated_change)
    {
        $this->delegated_change = $delegated_change;
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
    public function setAccount($account)
    {
        $this->account = $account;
    }

    /**
     * @return mixed
     */
    public function getExchanger()
    {
        return $this->exchanger;
    }

    /**
     * @param mixed $exchanger
     */
    public function setExchanger($exchanger)
    {
        $this->exchanger = $exchanger;

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
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * @param Transaction $transaction
     */
    public function setTransaction($transaction)
    {
        $this->transaction_ref = $transaction->getId();
    }

    /**
     * @return mixed
     */
    public function getCreditcard()
    {
        return $this->creditcard;
    }

    /**
     * @param mixed $creditcard
     */
    public function setCreditcard($creditcard): void
    {
        $this->creditcard = $creditcard;
    }

    /**
     * @return mixed
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param mixed $sender
     */
    public function setSender($sender): void
    {
        $this->sender = $sender;
    }

    /**
     * @return mixed
     */
    public function getTransactionRef()
    {
        return $this->transaction_ref;
    }

    /**
     * @param mixed $transaction_ref
     */
    public function setTransactionRef($transaction_ref): void
    {
        $this->transaction_ref = $transaction_ref;
    }


}