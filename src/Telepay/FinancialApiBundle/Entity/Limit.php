<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 4:47 PM
 */

namespace Telepay\FinancialApiBundle\Entity;


/**
 * Interface Limit
 * @package Telepay\FinancialApiBundle\Entity
 */
interface Limit {
    public function getSingle();
    public function getDay();
    public function getWeek();
    public function getMonth();
    public function getYear();
    public function getTotal();
}