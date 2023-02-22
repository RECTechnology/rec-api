<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 6:24 PM
 */


namespace App\Entity;


interface ExternallyDrived {
    public function getDriverName();
}