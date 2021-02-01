<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace App\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation as Serializer;

use Symfony\Component\Validator\Constraints as Assert;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Validator\Constraint as RECAssert;

/**
 * @ORM\Entity
 * @ORM\Table(name="delegated_change_data",uniqueConstraints={
 *     @ORM\UniqueConstraint(
 *          name="fk_idx",
 *          columns={
 *              "delegated_change_id",
 *              "account_id"
 *          }
 *     )
 * })
 */
class DelegatedChangeData extends AppObject {

    const STATUS_SUCCESS = "success";
    const STATUS_ERROR = "error";

    public function __construct()
    {
        $this->status = "new";
    }

    /**
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\DelegatedChange", inversedBy="data")
     * @Serializer\Groups({"admin"})
     */
    private $delegated_change;


    /**
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @RECAssert\IsUser()
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group")
     * @Serializer\Groups({"admin"})
     */
    private $account;

    /**
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @RECAssert\IsCommerce()
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group")
     * @Serializer\Groups({"admin"})
     */
    private $exchanger;


    /**
     * @Assert\CardScheme(schemes={"VISA", "MASTERCARD"})
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $pan;

    /**
     * @Assert\Regex(
     *     pattern="#^(0[1-9]|1[0-2])[/-]([2][01])?[1-9][0-9]$#",
     *     message="Invalid expiry format: must be mm/yy or mm/yyyy using values 01 to 12 for months and 2010 to 2100 values for years."
     * )
     * @RECAssert\IsNotExpired
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $expiry_date;

    /**
     * @Assert\Regex(
     *     pattern="#^\d\d\d$#",
     *     message="Invalid cvv2 format: must contain exactly three digits."
     * )
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $cvv2;

    /**
     * @Assert\NotNull
     * @ORM\Column(type="float", nullable=true)
     * @Serializer\Groups({"admin"})
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
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $creditcard_id;


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
    public function getPan()
    {
        return $this->pan;
    }

    /**
     * @param mixed $pan
     */
    public function setPan($pan)
    {
        $this->pan = $pan;
    }

    /**
     * @return mixed
     */
    public function getExpiryDate()
    {
        return $this->expiry_date;
    }

    /**
     * @param mixed $expiry_date
     */
    public function setExpiryDate($expiry_date)
    {
        $this->expiry_date = $expiry_date;
    }

    /**
     * @return mixed
     */
    public function getCvv2()
    {
        return $this->cvv2;
    }

    /**
     * @param mixed $cvv2
     */
    public function setCvv2($cvv2)
    {
        $this->cvv2 = $cvv2;
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
    public function getCreditcardId()
    {
        return $this->creditcard_id;
    }

    /**
     * @param mixed $creditcard_id
     */
    public function setCreditcardId($creditcard_id): void
    {
        $this->creditcard_id = $creditcard_id;
    }

}