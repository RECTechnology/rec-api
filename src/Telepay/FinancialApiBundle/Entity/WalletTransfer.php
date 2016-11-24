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
    private $wallet_in;

    /**
     * @ORM\Column(type="string")
     */
    private $wallet_out;

    /**
     * @ORM\Column(type="string")
     */
    private $currency_in;

    /**
     * @ORM\Column(type="string")
     */
    private $currency_out;

    /**
     * @ORM\Column(type="integer")
     */
    private $amount_in;

    /**
     * @ORM\Column(type="integer")
     */
    private $amount_out;

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
     * @param mixed $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
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
    public function getAmountIn()
    {
        return $this->amount_in;
    }

    /**
     * @param mixed $amount_in
     */
    public function setAmountIn($amount_in)
    {
        $this->amount_in = $amount_in;
    }

    /**
     * @return mixed
     */
    public function getAmountOut()
    {
        return $this->amount_out;
    }

    /**
     * @param mixed $amount_out
     */
    public function setAmountOut($amount_out)
    {
        $this->amount_out = $amount_out;
    }

    /**
     * @return mixed
     */
    public function getCurrencyIn()
    {
        return $this->currency_in;
    }

    /**
     * @param mixed $currency_in
     */
    public function setCurrencyIn($currency_in)
    {
        $this->currency_in = $currency_in;
    }

    /**
     * @return mixed
     */
    public function getCurrencyOut()
    {
        return $this->currency_out;
    }

    /**
     * @param mixed $currency_out
     */
    public function setCurrencyOut($currency_out)
    {
        $this->currency_out = $currency_out;
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
    public function getWalletIn()
    {
        return $this->wallet_in;
    }

    /**
     * @param mixed $wallet_in
     */
    public function setWalletIn($wallet_in)
    {
        $this->wallet_in = $wallet_in;
    }

    /**
     * @return mixed
     */
    public function getWalletOut()
    {
        return $this->wallet_out;
    }

    /**
     * @param mixed $wallet_out
     */
    public function setWalletOut($wallet_out)
    {
        $this->wallet_out = $wallet_out;
    }
}