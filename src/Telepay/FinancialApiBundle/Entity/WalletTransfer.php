<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 5:26 PM
 */

namespace Telepay\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="walletTransfer")
 */
class WalletTransfer {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $type;


    /**
     * @ORM\Column(type="string")
     */
    private $currency;

    /**
     * @ORM\Column(type="integer")
     */
    private $amount;

    /**
     * @ORM\Column(type="string")
     */
    private $status;

    /**
     * @ORM\Column(type="integer")
     */
    private $sentTimeStamp;

    /**
     * @ORM\Column(type="integer")
     */
    private $estimatedDeliveryTimeStamp;

    /**
     * @return mixed
     */
    public function getId(){
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id){
        $this->id = $id;
    }


    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param mixed $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
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
    public function getSentTimeStamp()
    {
        return $this->sentTimeStamp;
    }

    /**
     * @param mixed $sentTimeStamp
     */
    public function setSentTimeStamp($sentTimeStamp)
    {
        $this->sentTimeStamp = $sentTimeStamp;
    }

    /**
     * @return mixed
     */
    public function getEstimatedDeliveryTimeStamp()
    {
        return $this->estimatedDeliveryTimeStamp;
    }

    /**
     * @param mixed $estimatedDeliveryTimeStamp
     */
    public function setEstimatedDeliveryTimeStamp($estimatedDeliveryTimeStamp)
    {
        $this->estimatedDeliveryTimeStamp = $estimatedDeliveryTimeStamp;
    }
}