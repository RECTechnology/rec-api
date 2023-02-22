<?php

namespace App\Entity;

use App\Annotations\HybridProperty;
use App\Document\Transaction;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\ORM\Mapping as ORM;
use App\Annotations\StatusProperty;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraint as RECAssert;

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
    const STATUS_FAILED = 'failed';

    const EXPIRE_TIME = 600;

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
     *     "in-progress"={"to"={"done", "expired", "failed"}},
     *     "done"={"to"={"refunded"}},
     *     "expired"={"final"=true},
     *     "failed"={"final"=true},
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
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $refunded_amount;

    /**
     * @Serializer\Groups({"public"})
     * @Assert\Url()
     */
    private $payment_url;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     * @Assert\Regex("~^
                    [A-Za-z0-9-]*://                                 # protocol
                    (((?:[\_\.\pL\pN-]|%%[0-9A-Fa-f]{2})+:)?((?:[\_\.\pL\pN-]|%%[0-9A-Fa-f]{2})+)@)?  # basic auth
                    (
                    ([\pL\pN\pS\-\_\.])+(\.?([\pL\pN]|xn\-\-[\pL\pN-]+)+\.?) # a domain name
                    |                                                 # or
                    \d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}                    # an IP address
                    |                                                 # or
                    \[
                    (?:(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){6})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:::(?:(?:(?:[0-9a-f]{1,4})):){5})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){4})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,1}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){3})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,2}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){2})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,3}(?:(?:[0-9a-f]{1,4})))?::(?:(?:[0-9a-f]{1,4})):)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,4}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,5}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,6}(?:(?:[0-9a-f]{1,4})))?::))))
                    \]  # an IPv6 address
                    )
                    (:[0-9]+)?                              # a port (optional)
                    (?:/ (?:[\pL\pN\-._\~!$&\'()*+,;=:@]|%%[0-9A-Fa-f]{2})* )*      # a path
                    (?:\? (?:[\pL\pN\-._\~!$&\'()*+,;=:@/?]|%%[0-9A-Fa-f]{2})* )?   # a query (optional)
                    (?:\# (?:[\pL\pN\-._\~!$&\'()*+,;=:@/?]|%%[0-9A-Fa-f]{2})* )?   # a fragment (optional)
                    $~ixu")
     */
    private $ko_url;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     * @Assert\Regex("~^
                    [A-Za-z0-9-]*://                                 # protocol
                    (((?:[\_\.\pL\pN-]|%%[0-9A-Fa-f]{2})+:)?((?:[\_\.\pL\pN-]|%%[0-9A-Fa-f]{2})+)@)?  # basic auth
                    (
                    ([\pL\pN\pS\-\_\.])+(\.?([\pL\pN]|xn\-\-[\pL\pN-]+)+\.?) # a domain name
                    |                                                 # or
                    \d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}                    # an IP address
                    |                                                 # or
                    \[
                    (?:(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){6})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:::(?:(?:(?:[0-9a-f]{1,4})):){5})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){4})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,1}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){3})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,2}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){2})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,3}(?:(?:[0-9a-f]{1,4})))?::(?:(?:[0-9a-f]{1,4})):)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,4}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,5}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,6}(?:(?:[0-9a-f]{1,4})))?::))))
                    \]  # an IPv6 address
                    )
                    (:[0-9]+)?                              # a port (optional)
                    (?:/ (?:[\pL\pN\-._\~!$&\'()*+,;=:@]|%%[0-9A-Fa-f]{2})* )*      # a path
                    (?:\? (?:[\pL\pN\-._\~!$&\'()*+,;=:@/?]|%%[0-9A-Fa-f]{2})* )?   # a query (optional)
                    (?:\# (?:[\pL\pN\-._\~!$&\'()*+,;=:@/?]|%%[0-9A-Fa-f]{2})* )?   # a fragment (optional)
                    $~ixu")
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
     * @Serializer\Groups({"public"})
     * @Assert\Choice({"mobile", "desktop"})
     * @Assert\NotBlank()
     */
    private $payment_type;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\Pos", inversedBy="payment_orders")
     * @Serializer\Groups({"public"})
     */
    private $pos;

    /**
     * @var Transaction $payment_transaction
     * @HybridProperty(
     *     targetEntity="App\Document\Transaction",
     *     identifier="payment_transaction_id",
     *     manager="doctrine_mongodb.odm.document_manager"
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
     *     targetEntity="App\Document\Transaction",
     *     identifier="refund_transaction_id",
     *     manager="doctrine_mongodb.odm.document_manager"
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
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"public"})
     */
    private $retries = 0;

    public function incrementRetries(): void
    {
        $this->retries += 1;
    }

    /**
     * @return mixed
     */
    public function getRetries()
    {
        return $this->retries;
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


    /**
     * Get the value of payment_type
     */ 
    public function getPaymentType()
    {
        return $this->payment_type;
    }

    /**
     * Set the value of payment_type
     *
     * @return  self
     */ 
    public function setPaymentType($payment_type)
    {
        $this->payment_type = $payment_type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRefundedAmount()
    {
        return $this->refunded_amount;
    }

    /**
     * @param mixed $refunded_amount
     */
    public function setRefundedAmount($refunded_amount): void
    {
        $this->refunded_amount = $refunded_amount;
    }
}
