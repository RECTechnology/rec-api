<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 5:26 PM
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="walletConf")
 */
class WalletConf {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $priority;

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
    private $minBalance;

    /**
     * @ORM\Column(type="integer")
     */
    private $maxBalance;

    /**
     * @ORM\Column(type="integer")
     */
    private $perfectBalance;

    /**
     * @ORM\Column(type="integer")
     */
    private $fixedAmount;

    /**
     * @ORM\Column(type="integer")
     */
    private $maxTime;

    /**
     * @ORM\Column(type="boolean")
     */
    private $storehouse;

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
    public function getMinBalance()
    {
        return $this->minBalance;
    }

    /**
     * @param mixed $minBalance
     */
    public function setMinBalance($minBalance)
    {
        $this->minBalance = $minBalance;
    }

    /**
     * @return mixed
     */
    public function getMaxBalance()
    {
        return $this->maxBalance;
    }

    /**
     * @param mixed $maxBalance
     */
    public function setMaxBalance($maxBalance)
    {
        $this->maxBalance = $maxBalance;
    }

    /**
     * @return mixed
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param mixed $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
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
    public function getFixedAmount()
    {
        return $this->fixedAmount;
    }

    /**
     * @param mixed $fixedAmount
     */
    public function setFixedAmount($fixedAmount)
    {
        $this->fixedAmount = $fixedAmount;
    }

    /**
     * @return mixed
     */
    public function getPerfectBalance()
    {
        return $this->perfectBalance;
    }

    /**
     * @param mixed $perfectBalance
     */
    public function setPerfectBalance($perfectBalance)
    {
        $this->perfectBalance = $perfectBalance;
    }

    /**
     * @return mixed
     */
    public function getMaxTime()
    {
        return $this->maxTime;
    }

    /**
     * @param mixed $maxTime
     */
    public function setMaxTime($maxTime)
    {
        $this->maxTime = $maxTime;
    }

    /**
     * @return mixed
     */
    public function isStorehouse()
    {
        return $this->storehouse;
    }

    /**
     * @return mixed
     */
    public function getStorehouse()
    {
        return $this->storehouse;
    }

    /**
     * @param mixed $storehouse
     */
    public function setStorehouse($storehouse)
    {
        $this->storehouse = $storehouse;
    }
}