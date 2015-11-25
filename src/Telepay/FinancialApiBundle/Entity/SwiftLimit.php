<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 4:47 PM
 */

namespace Telepay\FinancialApiBundle\Entity;


use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 * @ORM\Table(name="swift_limit")
 */
class SwiftLimit implements Limit {


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
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\Client")
     */
    private $client;

    /**
     * @ORM\Column(type="string")
     */
    private $cname;

    private $scale;

    /**
     * @ORM\Column(type="string")
     */
    private $currency;

    public static function createFromController($cname, Client $client){
        $limit = new SwiftLimit();
        $limit->setCname($cname);
        $limit->setSingle(0);
        $limit->setDay(0);
        $limit->setWeek(0);
        $limit->setMonth(0);
        $limit->setYear(0);
        $limit->setTotal(0);
        $limit->setClient($client);
        return $limit;
    }


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
     * @return mixed
     */
    public function getCname()
    {
        return $this->cname;
    }

    /**
     * @param mixed $cname
     */
    public function setCname($cname)
    {
        $this->cname = $cname;
    }

    //TODO quitar esto de aqui porque se repite en varios sitios
    public function setScale($currency)
    {
        $scale=0;
        switch($currency){
            case "EUR":
                $scale=2;
                break;
            case "MXN":
                $scale=2;
                break;
            case "USD":
                $scale=2;
                break;
            case "BTC":
                $scale=8;
                break;
            case "FAC":
                $scale=8;
                break;
            case "PLN":
                $scale=2;
                break;
            case "":
                $scale=0;
                break;
        }
        $this-> scale=$scale;
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
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param mixed $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

}