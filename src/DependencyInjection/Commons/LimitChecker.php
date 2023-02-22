<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 7:02 PM
 */

namespace App\DependencyInjection\Commons;


use App\Entity\Limit;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LimitChecker {

    /**
     * Checks if $status Limit is less or equal than $configured Limit
     * @param Limit $status
     * @param Limit $configured
     * @return bool
     */
    public function leq(Limit $status, Limit $configured){
        if($configured->getSingle()<0 or $status->getSingle() <= $configured->getSingle());
            else throw new HttpException(412,'Single Limit exceeded.');
        if($configured->getDay()<0 or  $status->getDay() <= $configured->getDay());
            else throw new HttpException(412,'Day Limit exceeded.');
        if($configured->getWeek()<0 or $status->getWeek() <= $configured->getWeek());
            else throw new HttpException(412,'Week Limit exceeded.');
        if($configured->getMonth()<0 or $status->getMonth() <= $configured->getMonth());
            else throw new HttpException(412,'Month Limit exceeded.');
        if($configured->getYear()<0 or $status->getYear() <= $configured->getYear());
            else throw new HttpException(412,'Year Limit exceeded.');
        if($configured->getTotal()<0 or $status->getTotal() <= $configured->getTotal());
            else throw new HttpException(412,'Total Limit exceeded.');
        return true;
    }
}
