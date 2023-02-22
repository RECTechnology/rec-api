<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 4:47 PM
 */

namespace App\Entity;


use Doctrine\ORM\Mapping as ORM;

use App\Financial\Currency;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="tier_limits", uniqueConstraints={@ORM\UniqueConstraint(columns={"tier", "currency", "method"})})
 * @UniqueEntity(fields = {"tier", "currency", "method"})
 */
class TierLimit implements Limit {


    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="bigint")
     */
    private $single;

    /**
     * @ORM\Column(type="bigint")
     */
    private $day;

    /**
     * @ORM\Column(type="bigint")
     */
    private $week;

    /**
     * @ORM\Column(type="bigint")
     */
    private $month;

    /**
     * @ORM\Column(type="bigint")
     */
    private $year;

    /**
     * @ORM\Column(type="bigint")
     */
    private $total;

    /**
     * @ORM\Column(type="string")
     */
    private $method;

    /**
     * @ORM\Column(type="string")
     */
    private $tier;

    private $scale;

    public function createDefault($tier, $method, $currency){
        $this->day = 0;
        $this->single = 0;
        $this->week = 0;
        $this->month = 0;
        $this->year = 0;
        $this->total = 0;
        $this->method = $method;
        $this->tier = $tier;
        $this->currency = $currency;
    }

    /**
     * @ORM\Column(type="string")
     */
    private $currency;

    /**
     * @return mixed
     */
    public function getSingle()
    {
        return $this->single;
    }

    /**
     * @param mixed $single
     */
    public function setSingle($single)
    {
        $this->single = $single;
    }

    /**
     * @return mixed
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * @param mixed $day
     */
    public function setDay($day)
    {
        $this->day = $day;
    }

    /**
     * @return mixed
     */
    public function getWeek()
    {
        return $this->week;
    }

    /**
     * @param mixed $week
     */
    public function setWeek($week)
    {
        $this->week = $week;
    }

    /**
     * @return mixed
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * @param mixed $month
     */
    public function setMonth($month)
    {
        $this->month = $month;
    }

    /**
     * @return mixed
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * @param mixed $year
     */
    public function setYear($year)
    {
        $this->year = $year;
    }

    /**
     * @return mixed
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param mixed $total
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return mixed
     */
    public function getTier()
    {
        return $this->tier;
    }

    /**
     * @param mixed $tier
     */
    public function setTier($tier)
    {
        $this->tier = $tier;
    }

    /**
     * @return mixed
     */
    public function getScale()
    {
        return Currency::$SCALE[$this->currency];
    }

    /**
     * @param mixed $scale
     */
    public function setScale($currency)
    {
        $this->scale = Currency::$SCALE[$currency];
    }

}