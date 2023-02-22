<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/13/15
 * Time: 6:07 PM
 */

namespace App\Financial;

interface MoneyStorageInterface {
    public function getBalance();
    public function getCurrency();
    public function getWaysOut();
    public function getWaysIn();
}