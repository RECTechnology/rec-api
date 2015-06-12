<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/10/15
 * Time: 11:19 PM
 */

namespace Telepay\FinancialApiBundle\Entity;


interface OwnByUserInterface {
    public function getUser();
}