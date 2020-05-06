<?php

namespace App\FinancialApiBundle\Entity;


use App\FinancialApiBundle\Annotations\StatusProperty;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class PaymentOrderNotification
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity()
 */
class PaymentOrderNotification extends AppObject implements Notification, Stateful {

    use StatefulTrait;

    const STATUS_RETRYING = "retrying";
    const STATUS_NOTIFIED = "notified";
    const STATUS_EXPIRED = "expired";

    const EXPIRE_TIME = 24 * 3600;

    /**
     * @var string $status
     * @ORM\Column(type="string")
     * @StatusProperty(choices={
     *     "created"={"to"={"retrying", "notified"}},
     *     "retrying"={"to"={"notified", "expired"}},
     *     "expired"={"final"=true},
     *     "notified"={"final"=true}
     * }, initial_statuses={"created", "retrying"})
     * @Serializer\Groups({"manager"})
     */
    protected $status;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\PaymentOrder", inversedBy="notifications")
     * @Serializer\Groups({"admin"})
     */
    private $payment_order;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"admin"})
     * @Assert\Url()
     */
    private $url;

    /**
     * @ORM\Column(type="json")
     * @Serializer\Groups({"admin"})
     */
    private $content;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"admin"})
     */
    private $tries = 0;

    /**
     * @return mixed
     */
    public function getPaymentOrder()
    {
        return $this->payment_order;
    }

    /**
     * @param mixed $payment_order
     */
    public function setPaymentOrder($payment_order): void
    {
        $this->payment_order = $payment_order;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url): void
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content): void
    {
        $this->content = $content;
    }

    /**
     * @return mixed
     */
    public function getTries()
    {
        return $this->tries;
    }

    /**
     * @param mixed $tries
     */
    public function setTries($tries): void
    {
        $this->tries = $tries;
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

}
