<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 5/28/15
 * Time: 7:08 PM
 */

namespace App\FinancialApiBundle\Financial;

interface BankInterface {
    public function getName();
    public function getAddress();
    public function getBIC();
    public function getCountry();
}