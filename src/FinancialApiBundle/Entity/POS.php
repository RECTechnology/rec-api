<?php

namespace App\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\FinancialApiBundle\Entity\Order;
use App\FinancialApiBundle\Entity\AppObject;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * @ORM\Entity
 */
class POS extends AppObject
{
    public function __construct()
    {
        $this->orders = new ArrayCollection();
    }


    /**
     * @ORM\Column(type="boolean")
     * @Serializer\Groups({"user"})
     */
    public $active;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"user"})
     */
    public $notification_url;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"user"})
     */
    public $access_secret;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"user"})
     */
    public $access_key;

    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\Order", mappedBy="pos")
     * @Serializer\Groups({"user"})
     */
    public $orders;

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
    public function getNotification_url()
    {
        return $this->notification_url;
    }

    /**
     * Set the value of notification_url
     *
     * @return  self
     */
    public function setNotification_url($notification_url)
    {
        $this->notification_url = $notification_url;

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
    public function addOrder(Order $order)
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
}
