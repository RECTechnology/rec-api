<?php

namespace App\FinancialApiBundle\Entity;

use App\FinancialApiBundle\Annotations\HybridProperty;
use App\FinancialApiBundle\Document\Transaction;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\ORM\Mapping as ORM;
use App\FinancialApiBundle\Annotations\StatusProperty;
use Symfony\Component\Validator\Constraints as Assert;
use App\FinancialApiBundle\Validator\Constraint as RECAssert;

/**
 * @ORM\Entity
 * @RECAssert\ValidPaymentOrder()
 */
class PaymentOrder extends AppObject implements Stateful, HybridPersistent
{
    const STATUS_IN_PROGRESS = 'in-progress';
    const STATUS_EXPIRED = 'expired';
    const STATUS_DONE = 'done';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_REFUNDING = 'refunding';

    const EXPIRE_TIME = 300;

    use StatefulTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     * @Serializer\Groups({"public"})
     */
    protected $id;

    /**
     * @var string $status
     * @ORM\Column(type="string")
     * @StatusProperty(choices={
     *     "in-progress"={"to"={"done", "expired"}},
     *     "done"={"to"={"refunded"}},
     *     "expired"={"final"=true},
     *     "refunded"={"final"=true},
     * }, initial_statuses={"in-progress"})
     * @Serializer\Groups({"public"})
     */
    protected $status;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"user"})
     * @Assert\Ip()
     */
    private $ip_address;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    private $payment_address;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     * @Assert\NotBlank()
     */
    private $amount;

    /**
     * @Serializer\Groups({"public"})
     * @Assert\Url()
     */
    private $payment_url;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     * @Assert\Url()
     */
    private $ko_url;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     * @Assert\Url()
     */
    private $ok_url;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"admin"})
     * @Assert\NotBlank()
     */
    private $access_key;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"admin"})
     * @Assert\NotBlank()
     */
    private $signature;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"admin"})
     * @Assert\Choice({"hmac_sha256_v1"})
     * @Assert\NotBlank()
     */
    private $signature_version;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $reference;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $concept;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Pos", inversedBy="payment_orders")
     * @Serializer\Groups({"public"})
     */
    private $pos;

    /**
     * @var Transaction $payment_transaction
     * @HybridProperty(
     *     targetEntity="App\FinancialApiBundle\Document\Transaction",
     *     identifier="payment_transaction_id",
     *     manager="doctrine.odm.mongodb.document_manager"
     * )
     * @Serializer\Groups({"admin"})
     */
    private $payment_transaction;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"sadmin"})
     */
    private $payment_transaction_id;


    /**
     * @var Transaction $refund_transaction
     * @HybridProperty(
     *     targetEntity="App\FinancialApiBundle\Document\Transaction",
     *     identifier="refund_transaction_id",
     *     manager="doctrine.odm.mongodb.document_manager"
     * )
     * @Serializer\Groups({"admin"})
     */
    private $refund_transaction;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"sadmin"})
     */
    private $refund_transaction_id;


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

    /**
     * @return mixed
     */
    public function getKoUrl()
    {
        return $this->ko_url;
    }

    /**
     * @param mixed $ko_url
     */
    public function setKoUrl($ko_url)
    {
        $this->ko_url = $ko_url;
    }

    /**
     * @return mixed
     */
    public function getOkUrl()
    {
        return $this->ok_url;
    }

    /**
     * @param mixed $ok_url
     */
    public function setOkUrl($ok_url)
    {
        $this->ok_url = $ok_url;
    }

    /**
     * @return mixed
     */
    public function getPos()
    {
        return $this->pos;
    }

    /**
     * @param mixed $pos
     */
    public function setPos($pos)
    {
        $this->pos = $pos;
    }

    /**
     * @return mixed
     */
    public function getIpAddress()
    {
        return $this->ip_address;
    }

    /**
     * @param mixed $ip_address
     */
    public function setIpAddress($ip_address): void
    {
        $this->ip_address = $ip_address;
    }

    /**
     * @return mixed
     */
    public function getPaymentAddress()
    {
        return $this->payment_address;
    }

    /**
     * @param mixed $payment_address
     */
    public function setPaymentAddress($payment_address): void
    {
        $this->payment_address = $payment_address;
    }

    /**
     * @return mixed
     */
    public function getAccessKey()
    {
        return $this->access_key;
    }

    /**
     * @param mixed $access_key
     */
    public function setAccessKey($access_key): void
    {
        $this->access_key = $access_key;
    }

    /**
     * @return mixed
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * @param mixed $signature
     * @return PaymentOrder
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSignatureVersion()
    {
        return $this->signature_version;
    }

    /**
     * @param mixed $signature_version
     * @return PaymentOrder
     */
    public function setSignatureVersion($signature_version)
    {
        $this->signature_version = $signature_version;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param mixed $reference
     * @return PaymentOrder
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getConcept()
    {
        return $this->concept;
    }

    /**
     * @param mixed $concept
     * @return PaymentOrder
     */
    public function setConcept($concept)
    {
        $this->concept = $concept;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPaymentUrl()
    {
        return $this->payment_url;
    }

    /**
     * @param mixed $payment_url
     */
    public function setPaymentUrl($payment_url): void
    {
        $this->payment_url = $payment_url;
    }

    /**
     * @return Transaction
     */
    public function getPaymentTransaction(): ?Transaction
    {
        return $this->payment_transaction;
    }

    /**
     * @param Transaction $payment_transaction
     */
    public function setPaymentTransaction(Transaction $payment_transaction): void
    {
        $this->payment_transaction = $payment_transaction;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return Transaction
     */
    public function getRefundTransaction(): Transaction
    {
        return $this->refund_transaction;
    }

    /**
     * @param Transaction $refund_transaction
     */
    public function setRefundTransaction(Transaction $refund_transaction): void
    {
        $this->refund_transaction = $refund_transaction;
    }

}
