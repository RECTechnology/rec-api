<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 5/28/15
 * Time: 8:05 PM
 */

namespace Telepay\FinancialApiBundle\Financial;


interface AddressInterface {
    public function toString();
    public function getDriver();
}