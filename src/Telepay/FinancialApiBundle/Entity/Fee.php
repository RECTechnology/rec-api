<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 4:47 PM
 */

namespace Telepay\FinancialApiBundle\Entity;


/**
 * Interface Fee
 * @package Telepay\FinancialApiBundle\Entity
 */
interface Fee {
    public function getFixed();
    public function getVariable();
}