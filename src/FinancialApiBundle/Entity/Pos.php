<?php

namespace App\FinancialApiBundle\Entity;

use JMS\Serializer\Annotation as Serializer;
use Doctrine\ORM\Mapping as ORM;
use App\FinancialApiBundle\Entity\PaymentOrder;
use App\FinancialApiBundle\Entity\AppObject;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity
 */
class Pos extends AppObject
{
    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->active = true;
        $this->access_key = sha1(random_bytes(32));
        $this->access_secret = base64_encode(random_bytes(32));
    }

    /**
     * @ORM\Column(type="boolean")
     * @Serializer\Groups({"user"})
     */
    private $active;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"user"})
     * @Assert\Url(
     *     protocols={"https"},
     *     message="Provided value is not valid, https is required"
     * )
     */
    private $notification_url;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"user"})
     */
    private $access_secret;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"user"})
     */
    private $access_key;

    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\PaymentOrder", mappedBy="pos")
     * @Serializer\Groups({"user"})
     */
    private $orders;

    /**
     * @ORM\OneToOne(targetEntity="App\FinancialApiBundle\Entity\Group", inversedBy="pos")
     * @Serializer\Groups({"user"})
     */
    private $account;

    /**
     * Get the value of active
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set the value of active
     *
     * @return  self
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get the value of notification_url
     */
    public function getNotificationUrl()
    {
        return $this->notification_url;
    }

    /**
     * Set the value of notification_url
     *
     * @param $notificationUrl
     * @return  self
     */
    public function setNotificationUrl($notificationUrl)
    {
        $this->notification_url = $notificationUrl;

        return $this;
    }

    /**
     * Get the value of orders
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * Get the value of orders
     */
    public function addOrder(PaymentOrder $order)
    {
        $this->orders->add($order);
    }

    /**
     * Set the value of orders
     *
     * @return  self
     */
    public function setOrders($orders)
    {
        $this->orders = $orders;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAccessSecret()
    {
        return $this->access_secret;
    }

    /**
     * @return mixed
     */
    public function getAccessKey()
    {
        return $this->access_key;
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
     * @return Pos
     */
    public function setAccount($account)
    {
        $this->account = $account;
        return $this;
    }
}