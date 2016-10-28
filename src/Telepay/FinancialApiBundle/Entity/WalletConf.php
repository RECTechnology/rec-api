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
     * @ORM\Column(type="float")
     */
    private $minBalance;

    /**
     * @ORM\Column(type="float")
     */
    private $maxBalance;

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
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @param mixed $driver
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;
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
    public function getMinAmountSent()
    {
        return $this->minAmountSent;
    }

    /**
     * @param mixed $minAmountSent
     */
    public function setMinAmountSent($minAmountSent)
    {
        $this->minAmountSent = $minAmountSent;
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
}