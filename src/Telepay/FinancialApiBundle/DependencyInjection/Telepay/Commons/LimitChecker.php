<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 7:02 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons;


use Telepay\FinancialApiBundle\Entity\Limit;

class LimitChecker {

    /**
     * Checks if $status Limit is less or equal than $configured Limit
     * @param Limit $status
     * @param Limit $configured
     * @return bool
     */
    public function leq(Limit $status, Limit $configured){

        return
            ($configured->getSingle()<0 or $status->getSingle() <= $configured->getSingle()) and
            ($configured->getDay()<0 or  $status->getDay() <= $configured->getDay()) and
            ($configured->getWeek()<0 or $status->getWeek() <= $configured->getWeek()) and
            ($configured->getMonth()<0 or $status->getMonth() <= $configured->getMonth()) and
            ($configured->getYear()<0 or $status->getYear() <= $configured->getYear()) and
            ($configured->getTotal()<0 or $status->getTotal() <= $configured->getTotal());
    }
}
