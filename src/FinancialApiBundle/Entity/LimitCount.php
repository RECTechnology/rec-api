<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 4:47 PM
 */

namespace App\FinancialApiBundle\Entity;


use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 * @ORM\Table(name="limit_counts")
 */
class LimitCount implements Limit {

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

//    /**
//     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\User")
//     */
//    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group")
     */
    private $group;

    /**
     * @ORM\Column(type="string")
     */
    private $cname;

    public static function createFromController($service_cname, Group $group){
        $limit = new LimitCount();
//        $limit->setUser($user);
        $limit->setGroup($group);
        $limit->setCname($service_cname);
        $limit->setSingle(0);
        $limit->setDay(0);
        $limit->setWeek(0);
        $limit->setMonth(0);
        $limit->setYear(0);
        $limit->setTotal(0);
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

//    /**
//     * @return mixed
//     */
//    public function getUser()
//    {
//        return $this->user;
//    }
//
//    /**
//     * @param mixed $user
//     */
//    public function setUser($user)
//    {
//        $this->user = $user;
//    }

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

    /**
     * @return mixed
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param mixed $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

}