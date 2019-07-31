<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 5:26 PM
 */

namespace App\FinancialApiBundle\Entity;

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
    private $walletOrigin;

    /**
     * @ORM\Column(type="string")
     */
    private $walletDestination;

    /**
     * @ORM\Column(type="string")
     */
    private $currencyOrigin;

    /**
     * @ORM\Column(type="string")
     */
    private $currencyDestination;

    /**
     * @ORM\Column(type="integer")
     */
    private $amountOrigin;

    /**
     * @ORM\Column(type="integer")
     */
    private $amountDestination;

    /**
     * @ORM\Column(type="integer")
     */
    private $estimatedCost;

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
     * @ORM\Column(type="string")
     */
    private $information;

    /**
     * @return mixed
     */
    public function getId(){
        return $this->id;
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

    /**
     * @return mixed
     */
    public function getAmountOrigin()
    {
        return $this->amountOrigin;
    }

    /**
     * @param mixed $amountOrigin
     */
    public function setAmountOrigin($amountOrigin)
    {
        $this->amountOrigin = $amountOrigin;
    }

    /**
     * @return mixed
     */
    public function getAmountDestination()
    {
        return $this->amountDestination;
    }

    /**
     * @param mixed $amountDestination
     */
    public function setAmountDestination($amountDestination)
    {
        $this->amountDestination = $amountDestination;
    }

    /**
     * @return mixed
     */
    public function getCurrencyOrigin()
    {
        return $this->currencyOrigin;
    }

    /**
     * @param mixed $currencyOrigin
     */
    public function setCurrencyOrigin($currencyOrigin)
    {
        $this->currencyOrigin = $currencyOrigin;
    }

    /**
     * @return mixed
     */
    public function getCurrencyDestination()
    {
        return $this->currencyDestination;
    }

    /**
     * @param mixed $currencyDestination
     */
    public function setCurrencyDestination($currencyDestination)
    {
        $this->currencyDestination = $currencyDestination;
    }

    /**
     * @return mixed
     */
    public function getEstimatedCost()
    {
        return $this->estimatedCost;
    }

    /**
     * @param mixed $estimatedCost
     */
    public function setEstimatedCost($estimatedCost)
    {
        $this->estimatedCost = $estimatedCost;
    }

    /**
     * @return mixed
     */
    public function getWalletOrigin()
    {
        return $this->walletOrigin;
    }

    /**
     * @param mixed $walletOrigin
     */
    public function setWalletOrigin($walletOrigin)
    {
        $this->walletOrigin = $walletOrigin;
    }

    /**
     * @return mixed
     */
    public function getWalletDestination()
    {
        return $this->walletDestination;
    }

    /**
     * @param mixed $walletDestination
     */
    public function setWalletDestination($walletDestination)
    {
        $this->walletDestination = $walletDestination;
    }

    /**
     * @return mixed
     */
    public function getInformation()
    {
        return $this->information;
    }

    /**
     * @param mixed $information
     */
    public function setInformation($information)
    {
        $this->information = $information;
    }
}