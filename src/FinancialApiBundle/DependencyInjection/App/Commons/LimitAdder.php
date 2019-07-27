<?php


namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use App\FinancialApiBundle\Entity\Limit;

class LimitAdder implements Limit {

    public function add(Limit $limit, $amount) {
        //$limit->setSingleTransaction($limit->getSingleTransaction()+$amount);
        $limit->setDay($limit->getDay()+$amount);
        $limit->setWeek($limit->getWeek()+$amount);
        $limit->setMonth($limit->getMonth()+$amount);
        $limit->setYear($limit->getYear()+$amount);
        $limit->setTotal($limit->getTotal()+$amount);

        return $limit;
    }

    public function restore(Limit $limit, $amount) {
        //$limit->setSingleTransaction($limit->getSingleTransaction()+$amount);
        $limit->setDay($limit->getDay()-$amount);
        $limit->setWeek($limit->getWeek()-$amount);
        $limit->setMonth($limit->getMonth()-$amount);
        $limit->setYear($limit->getYear()-$amount);
        $limit->setTotal($limit->getTotal()-$amount);

        return $limit;
    }


    public function getSingle()
    {
        // TODO: Implement getSingle() method.
    }

    public function getDay()
    {
        // TODO: Implement getDay() method.
    }

    public function getWeek()
    {
        // TODO: Implement getWeek() method.
    }

    public function getMonth()
    {
        // TODO: Implement getMonth() method.
    }

    public function getYear()
    {
        // TODO: Implement getYear() method.
    }

    public function getTotal()
    {
        // TODO: Implement getTotal() method.
    }
}