<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 5/28/15
 * Time: 7:15 PM
 */

namespace App\FinancialApiBundle\Financial;


class ObjectRegister implements RegisterInterface {

    private $objects;

    public function __construct(array $objects){
        $this->objects = $objects;
    }

    public function findAll()
    {
        return $this->objects;
    }
}