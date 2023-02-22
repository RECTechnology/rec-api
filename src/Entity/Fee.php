<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 4:47 PM
 */

namespace App\Entity;


/**
 * Interface Fee
 * @package App\Entity
 */
interface Fee {
    public function getFixed();
    public function getVariable();
}